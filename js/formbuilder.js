console.log("Bookings Form Builder JS Loaded");

document.addEventListener("input", (ev) => {
  let target = ev.target;
  if (target.matches(`.subject-name`)) {
    ev.stopImmediatePropagation();

    let tabId = target.closest(`.tabcontent`).id;
    document.querySelector(`[data-target="${tabId}"`).textContent =
      target.value;
  }
});


//Catch click events
window.addEventListener("click", (event) => {
  let target = event.target;
  
  if (target.matches(`.add.room`)) {
    target.closest('div').querySelector('.room-details-wrapper').classList.remove('hidden');
  }
});