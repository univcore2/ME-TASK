// assets/js/app.js
document.addEventListener("DOMContentLoaded", () => {

  // Quick Note Save (Dashboard)
  const saveNoteBtn = document.getElementById("saveNoteBtn");
  if (saveNoteBtn) {
    saveNoteBtn.addEventListener("click", async () => {
      const note = document.getElementById("quickNote").value.trim();
      const status = document.getElementById("noteStatus");
      status.textContent = "Saving...";

      // TODO: replace with real endpoint
      // const res = await fetch("/api/notes/save.php", { method:"POST", body: new URLSearchParams({note}) });
      // const data = await res.json();

      setTimeout(() => {
        status.textContent = "Saved ✓";
      }, 400);
    });
  }

  // Progress slider (Task View)
  const progressRange = document.getElementById("progressRange");
  const progressValue = document.getElementById("progressValue");
  if (progressRange && progressValue) {
    progressRange.addEventListener("input", () => {
      progressValue.textContent = `${progressRange.value}%`;
    });
  }

});
