<template>
  <div class="p-4 border rounded-lg shadow-sm">
    <div v-if="error" class="text-red-500 mb-4">
      {{ error }}
    </div>
    <div class="mb-4">
      <label for="file" class="block text-sm font-medium text-gray-700">Upload Excel or PDF</label>
      <input type="file" id="file" @change="handleFileChange" accept=".xlsx,.pdf" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
    </div>
  </div>
</template>

<script>
import * as XLSX from 'xlsx';
import * as pdfjsLib from 'pdfjs-dist/build/pdf';

// Setting workerSrc is important for pdf.js to work in most environments.
// Using a CDN is a simple way to get it running.
pdfjsLib.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjsLib.version}/pdf.worker.min.js`;

export default {
  name: 'FileUpload',
  data() {
    return {
      error: '',
    };
  },
  methods: {
    handleFileChange(event) {
      const file = event.target.files[0];
      if (!file) {
        return;
      }

      this.error = '';
      const fileExtension = file.name.split('.').pop().toLowerCase();

      if (fileExtension === 'xlsx') {
        this.parseExcel(file);
      } else if (fileExtension === 'pdf') {
        this.parsePdf(file);
      } else {
        this.error = 'Unsupported file type. Please upload an Excel (.xlsx) or PDF (.pdf) file.';
      }
    },
    parseExcel(file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          const firstSheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[firstSheetName];
          // Using `sheet_to_json` with `header: 1` gives an array of arrays.
          const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

          if (jsonData.length < 1) {
            this.error = 'Excel file is empty.';
            return;
          }

          // The first row is assumed to be the header.
          const headers = jsonData[0];
          const rows = jsonData.slice(1).map(row => {
            let rowData = {};
            headers.forEach((header, index) => {
              rowData[header] = row[index];
            });
            return rowData;
          });

          this.$emit('file-parsed', rows);
        } catch (err) {
          this.error = 'Error parsing Excel file.';
          console.error(err);
        }
      };
      reader.onerror = () => {
        this.error = 'Error reading file.';
      };
      reader.readAsArrayBuffer(file);
    },
    async parsePdf(file) {
        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const pdf = await pdfjsLib.getDocument({ data }).promise;
                let allRows = [];

                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const textContent = await page.getTextContent();

                    // This is a very basic PDF text extraction. It may not work for all PDF layouts.
                    // It assumes that each line in the PDF corresponds to a row of data.
                    let pageText = textContent.items.map(item => item.str).join('');
                    const lines = pageText.split(/\r\n?|\n/);

                    if (i === 1 && lines.length > 0) {
                      // Assume first line of first page is header
                      const headers = lines[0].split(/\s{2,}/); // Split by 2+ spaces
                      const rows = lines.slice(1).map(line => {
                        const values = line.split(/\s{2,}/);
                        let rowData = {};
                        headers.forEach((header, index) => {
                          rowData[header] = values[index];
                        });
                        return rowData;
                      });
                      allRows.push(...rows);
                    } else {
                      // For subsequent pages, assume no header
                       const rows = lines.map(line => {
                        // This part is tricky without knowing the header.
                        // This logic will likely fail for multi-page PDFs.
                        // A more robust implementation would be needed for complex cases.
                        return { raw: line }; // Fallback
                      });
                      allRows.push(...rows);
                    }
                }

                this.$emit('file-parsed', allRows);

            } catch (err) {
                this.error = 'Error parsing PDF file.';
                console.error(err);
            }
        };
        reader.onerror = () => {
            this.error = 'Error reading file.';
        };
        reader.readAsArrayBuffer(file);
    },
  },
};
</script>
