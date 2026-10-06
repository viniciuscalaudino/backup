import { defineConfig } from "vite";
import { createReadStream } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const estoqueImagePath = fileURLToPath(
  new URL("../backend/api/images/image.png", import.meta.url),
);
const productImagesDirectory = fileURLToPath(
  new URL("../backend/uploads/produtos/", import.meta.url),
);

export default defineConfig({
  root: "src",
  plugins: [
    {
      name: "estoque-product-image",
      configureServer(server) {
        server.middlewares.use(
          "/images/estoque/image.png",
          (_request, response) => {
            response.setHeader("Content-Type", "image/png");
            const stream = createReadStream(estoqueImagePath);
             stream.on("error", () => {
              response.statusCode = 404;
              response.end("Imagem não encontrada");
            });
            stream.pipe(response);
          },
        );
        server.middlewares.use("/images/produtos", (request, response) => {
          let filename;
          try {
            filename = path.basename(
              decodeURIComponent(request.url.split("?")[0]),
            );
          } catch {
            response.statusCode = 400;
            response.end("Nome de imagem inválido");
            return;
          }

          if (!filename || filename === "." || filename === "..") {
            response.statusCode = 404;
            response.end("Imagem não encontrada");
            return;
          }

          const imagePath = path.join(productImagesDirectory, filename);
          const stream = createReadStream(imagePath);
          const extension = path.extname(filename).toLowerCase();
          const contentTypes = {
            ".gif": "image/gif",
            ".jpeg": "image/jpeg",
            ".jpg": "image/jpeg",
            ".png": "image/png",
            ".webp": "image/webp",
          };
          if (contentTypes[extension]) {
            response.setHeader("Content-Type", contentTypes[extension]);
          }
          stream.on("error", () => {
            if (!response.headersSent) response.statusCode = 404;
            response.end("Imagem não encontrada");
          });
          stream.pipe(response);
        });
      },
    },
  ],
  server: {
    port: 8080,
    strictPort: true,
  },
  build: {
    outDir: "dist",
    emptyOutDir: true,
  },
});
