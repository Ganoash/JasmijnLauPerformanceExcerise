(function (blocks, element) {
  const el = element.createElement;

  blocks.registerBlockType("lau-performance-training/dashboard-schema", {
    title: "Training schema links",
    icon: "calendar-alt",
    category: "widgets",
    edit: function () {
      return el("p", {}, "Training schema links voor de ingelogde gebruiker.");
    },
    save: function () {
      return null;
    },
  });
})(window.wp.blocks, window.wp.element);
