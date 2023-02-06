import nodeResolve from "@rollup/plugin-node-resolve";
import babel from "@rollup/plugin-babel";
import terser from "@rollup/plugin-terser";
import commonjs from "@rollup/plugin-commonjs";
import replace from "@rollup/plugin-replace";
import typescript from "@rollup/plugin-typescript";
import jsx from "acorn-jsx";
import json from "@rollup/plugin-json";

export default {
  input: "meetings/src/index.ts",
  output: {
    file: "meetings/dist/bundle.js",
    format: "umd",
    name: "Meetings",
    sourcemap: true,
  },
  acornInjectPlugins: [jsx()],
  plugins: [
    typescript({
      tsconfig: "meetings/tsconfig.json",
    }),
    replace({
      preventAssignment: true,
      "process.env.NODE_ENV": JSON.stringify("production"),
    }),
    babel({
      babelHelpers: "bundled",
      presets: ["@babel/preset-react"],
    }),
    json(),
    commonjs(),
    nodeResolve(),
    terser(),
  ],
};
