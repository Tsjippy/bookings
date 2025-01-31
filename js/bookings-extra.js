let startDates      = document.querySelectorAll(`[name^='booking-startdate']`);
let endDates        = document.querySelectorAll(`[name^='booking-enddate']`);
let nights          = 0;
let pricePerNight   = document.querySelector(`.price-per-night`).value;
let amount          = parseInt(pricePerNight.match(/\d|\.|\-/g).join(''));
let currency        = pricePerNight.replace(amount, '');

// Calculate the nights
document.querySelectorAll(`[name^='booking-startdate']`).forEach((el, index) => {
    let date1               = new Date(el.value);
    let date2               = new Date(endDates[index].value);
    let DifferenceInTime    = date2.getTime() - date1.getTime();
    let DifferenceInDays    = Math.round(DifferenceInTime / (1000 * 3600 * 24));

    nights  = nights + DifferenceInDays;
});

// Show the total amount with the currency and the number with a thousand seperator
document.querySelectorAll(`.payment-amount`).forEach(el => el.value = currency + (nights * pricePerNight).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));