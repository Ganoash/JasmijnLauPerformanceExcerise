(function () {
  const config = window.lptSchemaEditor || {};
  const picker = document.getElementById("lpt-week-picker");

  if (!picker) {
    return;
  }

  picker.addEventListener("change", () => {
    if (!picker.value) {
      return;
    }

    const selectedDate = new Date(`${picker.value}T12:00:00`);
    const day = selectedDate.getDay();

    // Convert selected date to Monday of that week.
    const daysSinceMonday = (day + 6) % 7;
    selectedDate.setDate(selectedDate.getDate() - daysSinceMonday);

    const year = selectedDate.getFullYear();
    const month = String(selectedDate.getMonth() + 1).padStart(2, "0");
    const date = String(selectedDate.getDate()).padStart(2, "0");
    const weekStartDate = `${year}-${month}-${date}`;

    const url = new URL(window.location.href);

    url.searchParams.set("page", "lpt-schema-editor");
    url.searchParams.set("user_id", config.userId || "");
    url.searchParams.set("week_start_date", weekStartDate);

    url.searchParams.delete("updated");
    url.searchParams.delete("lpt_error");

    window.location.href = url.toString();
  });
})();
