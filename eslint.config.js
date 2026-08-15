import js from "@eslint/js";

export default [
  js.configs.recommended,
  {
    files: ["assets/**/*.js"],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: "script",
      globals: {
        document: "readonly",
        fetch: "readonly",
        FormData: "readonly",
        window: "readonly",
      },
    },
    rules: {
      "no-var": "error",
      "prefer-const": "error",
    },
  },
];
