import nodeResolve from "@rollup/plugin-node-resolve";
import babel from "@rollup/plugin-babel";
import terser from "@rollup/plugin-terser";
import commonjs from "@rollup/plugin-commonjs";
import replace from "@rollup/plugin-replace";

export default {
  input: "meetings/src/index.js",
  output: {
    file: "meetings/dist/bundle.js",
    format: "umd",
    name: "Meetings",
    sourcemap: true,
  },
  plugins: [
    replace({
      preventAssignment: true,
      "process.env.NODE_ENV": JSON.stringify("production"),
    }),
    babel({
      babelHelpers: "bundled",
      presets: ["@babel/preset-react"],
    }),
    commonjs(),
    nodeResolve(),
    terser(),
  ],
};
