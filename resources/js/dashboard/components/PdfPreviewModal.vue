<template>
  <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-40" @click.self="close">
    <div class="relative shadow-lg rounded-md bg-white w-11/12 md:w-3/4 lg:w-1/2 flex flex-col" style="max-height: 90vh;">
      <!-- Modal Header -->
      <div class="flex justify-between items-center p-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Previsualización de PDF</h3>
        <div class="flex items-center space-x-2">
            <button @click="printPdf" class="p-2 text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </button>
            <button @click="downloadPdf" class="p-2 text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            </button>
            <button @click="close" class="p-2 text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="p-4 overflow-auto" style="max-height: 75vh;">
        <div v-if="loading" class="flex justify-center items-center h-64">
          <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
        <div v-if="error" class="text-red-500 text-center">{{ error }}</div>
        <div v-show="!loading" ref="pdfContainer"></div>
      </div>
    </div>
  </div>
</template>

<script>
import * as pdfjsLib from 'pdfjs-dist/build/pdf';
pdfjsLib.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjsLib.version}/pdf.worker.js`;

export default {
  name: 'PdfPreviewModal',
  props: {
    show: {
      type: Boolean,
      required: true,
    },
    pdfUrl: {
      type: String,
      default: null,
    },
    token: {
        type: String,
        required: true,
    }
  },
  data() {
    return {
      loading: false,
      error: null,
      pdfDoc: null,
    };
  },
  watch: {
    show(newValue) {
      if (newValue && this.pdfUrl) {
        this.loadPdf();
      } else {
        this.cleanup();
      }
    }
  },
  methods: {
    close() {
      this.$emit('close');
    },
    cleanup() {
        if (this.$refs.pdfContainer) {
            this.$refs.pdfContainer.innerHTML = '';
        }
        this.pdfDoc = null;
        this.error = null;
    },
    async loadPdf() {
      this.loading = true;
      this.cleanup();

      try {
        const response = await fetch(this.pdfUrl, {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });

        if (!response.ok) {
            throw new Error(`Error al cargar el PDF: ${response.statusText}`);
        }

        const pdfData = await response.arrayBuffer();
        const loadingTask = pdfjsLib.getDocument({ data: pdfData });

        this.pdfDoc = await loadingTask.promise;
        this.renderAllPages();

      } catch (error) {
        console.error('Error loading PDF:', error);
        this.error = 'No se pudo cargar el PDF. Inténtelo de nuevo más tarde.';
      } finally {
        this.loading = false;
      }
    },
    async renderAllPages() {
        const container = this.$refs.pdfContainer;
        for (let pageNum = 1; pageNum <= this.pdfDoc.numPages; pageNum++) {
            const page = await this.pdfDoc.getPage(pageNum);
            const viewport = page.getViewport({ scale: 1.5 });
            const canvas = document.createElement('canvas');
            canvas.className = 'mb-4';
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            container.appendChild(canvas);

            const renderContext = {
                canvasContext: context,
                viewport: viewport,
            };
            await page.render(renderContext).promise;
        }
    },
    downloadPdf() {
        const link = document.createElement('a');
        link.href = this.pdfUrl;
        link.target = '_blank';
        link.download = this.pdfUrl.split('/').pop();
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    },
    printPdf() {
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = this.pdfUrl;
        document.body.appendChild(iframe);
        iframe.onload = () => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {
                console.error("Print failed:", e);
                this.error = "La impresión falló. Intente descargar el PDF y abrirlo en un visor externo.";
            }
        };
    }
  }
};
</script>
