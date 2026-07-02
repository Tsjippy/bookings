console.log("Bookings Form Builder JS Loaded");

document.addEventListener("input", (ev) => {
  let target = ev.target;
  if (target.matches(`.subject-name`) || target.matches(`.room-name`)) {
    ev.stopImmediatePropagation();

    let tabId = target.closest(`.tabcontent`).id;
    document.querySelector(`[data-target="${tabId}"`).textContent =
      target.value;
  }
});

//Catch click events
window.addEventListener("click", (event) => {
  let target = event.target;
  
  if (target.matches(`.show-rooms-wrapper`)) {
    // Hide the button
    target.classList.add('hidden');

    let wrapper = target.closest('div').querySelector('.room-details-wrapper');

    // Add a new room
    wrapper.querySelector(`.add.room.button`).click();
  
    // SHow the rooms
    wrapper.classList.remove('hidden');
  }
});