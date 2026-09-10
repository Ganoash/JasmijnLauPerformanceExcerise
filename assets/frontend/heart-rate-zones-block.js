(function (blocks, element) {
  const el = element.createElement;

  blocks.registerBlockType("lau-performance-training/heart-rate-zones", {
    title: "Hartslagzones",
    icon: "heart",
    category: "widgets",
    edit: function () {
      return el("p", {}, "Hartslagzones voor de ingelogde gebruiker.");
    },
    save: function () {
      return null;
    },
  });
})(window.wp.blocks, window.wp.element);
