(function () {
  const config = window.lptSchemaView || {};
  const rows = Array.from(document.querySelectorAll(".lpt-training-row"));

  function parseDistance(value) {
    if (value === "") {
      return 0;
    }

    const parsed = Number.parseFloat(String(value).replace(",", "."));
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
  }

  function setStatus(row, message, isError) {
    const status = row.querySelector(".lpt-save-status");
    if (!status) {
      return;
    }

    status.textContent = message;
    status.classList.toggle("is-error", Boolean(isError));
  }

  function updateTotals() {
    const totals = {
      running: 0,
      cycling: 0,
      swimming: 0,
    };

    rows.forEach((row) => {
      const input = row.querySelector('[data-field="actual_distance"]');
      const category = String(row.dataset.category || "").toLowerCase();
      const unit = String(row.dataset.unit || "").toLowerCase();
      const value = parseDistance(input ? input.value : "");

      if (category === "running") {
        totals.running += value;
      } else if (category === "cycling") {
        totals.cycling += value;
      } else if (category === "swimming") {
        totals.swimming += unit === "meters" || unit === "meter" ? value / 1000 : value;
      }
    });

    Object.entries(totals).forEach(([key, value]) => {
      const target = document.querySelector(`[data-total="${key}"]`);
      if (target) {
        target.textContent = value.toFixed(2);
      }
    });
  }

  function saveField(row, field, value, attempt) {
    const body = new FormData();
    body.append("action", "lpt_save_training_feedback");
    body.append("nonce", config.nonce || "");
    body.append("training_id", row.dataset.trainingId || "");
    body.append("field", field);
    body.append("value", value);

    setStatus(row, "Opslaan...", false);

    return fetch(config.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("save failed");
        }
        return response.json();
      })
      .then((payload) => {
        if (!payload || payload.success !== true) {
          throw new Error("save rejected");
        }
        setStatus(row, "Opgeslagen", false);
      })
      .catch(() => {
        if (attempt < 3) {
          return saveField(row, field, value, attempt + 1);
        }
        setStatus(row, "Niet opgeslagen", true);
      });
  }

  rows.forEach((row) => {
    row.querySelectorAll("[data-field]").forEach((field) => {
      field.addEventListener("input", () => {
        if (field.dataset.field === "actual_distance") {
          updateTotals();
        }
      });

      field.addEventListener("blur", () => {
        if (field.dataset.field === "actual_distance") {
          updateTotals();
        }
        saveField(row, field.dataset.field, field.value, 1);
      });
    });
  });

  updateTotals();
})();
