import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import replace from "@rollup/plugin-replace";

export default defineConfig({
  build: {
    outDir: "./meetings/dist",
    lib: {
      entry: "./meetings/src/index.ts",
      formats: ["umd"],
      name: "Meetings",
      fileName: () => `bundle.js`,
    },
    rollupOptions: {
      plugins: [
        replace({
          preventAssignment: true,
          "process.env.NODE_ENV": JSON.stringify("production"),
        }),
      ],
    },
  },
  plugins: [
    react({
      exclude: [/node_modules/, /dist/],
    }),
  ],
  appType: "custom",
});
