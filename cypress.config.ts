import { defineConfig } from "cypress";

export default defineConfig({
  e2e: {
    baseUrl: "http://vue2.test",
    setupNodeEvents(on, config) {
      // implement node event listeners here

    },
  },
});
