(function () {
  const page = document.querySelector(".lpt-goals-page");

  document.querySelectorAll("[data-lpt-delete-goal]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      if (!window.confirm("Doel definitief verwijderen?")) {
        event.preventDefault();
      }
    });
  });

  if (!page || page.dataset.goalCompleted !== "1") {
    return;
  }

  const colors = ["#f97316", "#1f7a3f", "#2563eb", "#eab308", "#dc2626"];
  const layer = document.createElement("div");
  layer.className = "lpt-goal-celebration";

  for (let index = 0; index < 36; index += 1) {
    const piece = document.createElement("span");
    piece.style.left = `${Math.random() * 100}%`;
    piece.style.backgroundColor = colors[index % colors.length];
    piece.style.animationDelay = `${Math.random() * 280}ms`;
    piece.style.animationDuration = `${900 + Math.random() * 700}ms`;
    layer.appendChild(piece);
  }

  document.body.appendChild(layer);
  window.setTimeout(() => {
    layer.remove();
  }, 2000);
})();
