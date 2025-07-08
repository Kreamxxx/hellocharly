({
  baseUrl: "src",
  name: "main",
  out: "build/main.min.js",
  optimize: "uglify",
  paths: {
    jquery: "empty:",
    "core/ajax": "empty:",
    "core/notification": "empty:"
  }
})
