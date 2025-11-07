function reset(modal, onlyEnd=false, skipRoomSelector=true){
    if(!onlyEnd){
        modal.querySelector('.booking-startdate').value     = '';
        modal.querySelectorAll('.calendar.day.startdate').forEach(el => el.classList.remove('startdate'));
        modal.querySelectorAll('.available.unavailable').forEach(dt => dt.classList.remove('unavailable'));

        modal.querySelector('.booking-date-label-wrapper.enddate').classList.add('disabled')
    }
    modal.querySelector('.booking-enddate').value       = '';
    
    modal.querySelectorAll('.calendar.day.enddate').forEach(el => el.classList.remove('enddate'));

    modal.querySelectorAll('.inbetween').forEach(dt => dt.classList.remove('inbetween'));    

    if(!skipRoomSelector){
        modal.querySelectorAll('.room-selector').forEach(el => el.checked=false);    

        if(modal.querySelectorAll('.room-selector').length > 1){
            modal.querySelectorAll('.room-wrapper:not(.hidden)').forEach(el => el.classList.add('hidden'));
        }
    }
}

async function getMonth(target){
    let wrapper         = target.closest('.bookings-wrap');
    let monthContainers = wrapper.querySelectorAll(`.month-container[data-month="${target.dataset.month}"][data-year="${target.dataset.year}"]`);
    let type;

    // we clicked the previous button
    if(target.closest('.prev') != null){
        type    = 'prev';
        // hide every second calendar, so that we can show a new calendar to the left
        wrapper.querySelectorAll('.calendar.table .month-container:not(.hidden)').forEach(
            (el, index) => {
                if(index % 2 != 0){ // uneven index
                    el.classList.add('hidden');
                }
            }
        );
    }else{
        type    = 'next';

        // hide every first calendar, so that we can show a new calendar to the right
        wrapper.querySelectorAll('.calendar.table .month-container:not(.hidden)').forEach(
            (el, index) => {
                if(index % 2 == 0){ // even index
                    el.classList.add('hidden');
                }
            }
        );
    }

    // month does not exist yet, request the data
    if(monthContainers.length == 0){
        let formData    = new FormData();
        formData.append('month', target.dataset.month);
        formData.append('year', target.dataset.year);
        formData.append('subject', wrapper.dataset.subject);
        formData.append('form-id', wrapper.dataset.formId);
        formData.append('type', type);

        if(wrapper.dataset.elementId != undefined){
            formData.append('element-id', wrapper.dataset.elementId);
        }

        if(wrapper.dataset.shortcodeId != undefined){
            formData.append('shortcode-id', wrapper.dataset.shortcodeId);
        }

        let position;
        if(type == 'prev'){
            // insert the loader at the left
            position    = 'afterBegin';
        }else{
            position    = 'beforeEnd';
        }

        wrapper.querySelectorAll('.room-wrapper .month-wrapper').forEach(monthWrapper => {
            let loader  = Main.showLoader(monthWrapper, false, 100);
            monthWrapper.insertAdjacentElement(position, loader);

            loader.style.marginLeft = '75px';
            loader.style.marginTop  = '75px';
        });
            
        let response = await FormSubmit.fetchRestApi('bookings/get_next_month', formData);

        if(response){
            // add the new months to each room
            wrapper.querySelectorAll('.room-wrapper .loader-wrapper').forEach((el, index) => {
                el.outerHTML        = response.months[index];
            });

            // hide current navigator and add new one
            wrapper.querySelector('.navigators .navigator:not(.hidden)').classList.add('hidden');
            wrapper.querySelector('.navigators').insertAdjacentHTML('beforeEnd', response.navigator);
            wrapper.querySelectorAll('.booking.details-wrapper').forEach(el => el.insertAdjacentHTML('beforeEnd', response.details));
        }
    }else{
        // hide the current navigator
        wrapper.querySelector('.navigators .navigator:not(.hidden)').classList.add('hidden');

        // show the new one
        wrapper.querySelector(`.navigator[data-month="${target.dataset.month}"][data-year="${target.dataset.year}"]`).classList.remove('hidden');
        
        // Show the month calendar
        monthContainers.forEach(el=>el.classList.remove('hidden'));
    }
}

async function approve(target){    
    let formData    = new FormData();
    formData.append('id', target.dataset.id);
    formData.append('form-id', target.dataset.formId);

    let row         = target.closest('tr');

    let cell        = target.closest('td');

    let initContent = cell.outerHTML;

    Main.showLoader(cell);
        
    let response = await FormSubmit.fetchRestApi('bookings/approve', formData);

    if(response){

        // Remove table if empty
        if(row.closest('tbody').rows.length == 1){
            row.closest('table').remove()
        }else{
            // remove the row
            row.remove();
        }

        Main.displayMessage(response.message);

        let building        = response.subject.split(';')[0];
        let room            = response.subject.split(';')[1];
        let querySelector   = `.bookings-wrap[data-subject='${building}']`;

        if(room != undefined){
            querySelector   += ` .room-wrapper[data-room='${room}']`;
        }

        let subjectWrapper  = document.querySelector(querySelector);

        for (let i = 0; i < response.months.length; i++) {
            // Get the calendar
            subjectWrapper.querySelectorAll(`.month-container[data-month='${response.months[i]}'][data-year='${response.years[i]}']`).forEach(el=>el.outerHTML   = response.html);
        }

        // Add the details
        subjectWrapper.closest('.bookings-wrap').querySelector(`.booking.details-wrapper`).insertAdjacentHTML('beforeEnd', response.details);
        
    }else{
        row.querySelector('.loader-wrapper').outerHTML  = initContent;
    }
}

async function remove(target){    
    let formData    = new FormData();
    formData.append('id', target.dataset.id);
    let row         = target.closest('tr');

    Main.showLoader(target.closest('td'));
        
    let response = await FormSubmit.fetchRestApi('bookings/remove', formData);

    if(response){
        // Remove table if empty
        if(row.closest('tbody').rows.length == 1){
            row.closest('table').remove()
        }else{
            // remove the row
            row.remove();
        }

        Main.displayMessage(response);
    }
}

async function loadPost(target){    
    let formData    = new FormData();
    formData.append('post-id', target.dataset.postId);

    Main.showLoader(target.firstElementChild);
        
    let response = await FormSubmit.fetchRestApi('bookings/load_post', formData);

    if(response){
        // Remove table if empty
        target.innerHTML    = response;

        document.querySelectorAll('.page-edit.hidden').forEach(el=>{
            el.classList.remove('hidden');
        });
    }else{
        target.innerHTML    = "Description not available";
    }
}

function changeBookingData(target){
    let selector;
    let el  = document.querySelector(`.booking-subject-selector:checked`);
    if(el == null){
        selector    = '.booking.modal';
        console.log(selector);
    }else{
        selector    = `[name="${el.value}-modal"]`;
    }

    Main.showModal(document.querySelector(selector));
}

function storeDates(target){
    let modal   = target.closest('.modal');

    // remove all previous dates
    target.closest('form').querySelectorAll('.selected-booking-dates .clone-div').forEach((el, index)=>{
        if(index>0){
            el.remove();
        }
    });

    let parent          = target.closest('form').querySelector('.selected-booking-dates');
    let original        = parent.querySelector('.clone-div');

    // set values and create clones
    modal.querySelectorAll('.startdate').forEach((el, index)=>{
        let clone		= original;

        if(index > 0){
            clone		= FormFunctions.cloneNode(original);
        }
    
        let startEl     = clone.querySelector('[name^="booking-startdate"]');
        let endEl       = clone.querySelector('[name^="booking-enddate"]');
        let roomEl      = clone.querySelector('[name^="booking-rooms"]');
        let room        = el.closest('.room-wrapper').dataset.room;

        startEl.value   = el.dataset.isodate;
        endEl.value     = modal.querySelectorAll('.day.enddate')[index].dataset.isodate;

        if(room == undefined){
            roomEl.closest('div').classList.add('hidden');
            roomEl.value    = '';
        }else{
            roomEl.closest('div').classList.remove('hidden');
            roomEl.value    = room;
        }

        parent.insertAdjacentElement('beforeEnd', clone);
        
        FormFunctions.fixNumbering(parent);
    });

    parent.classList.remove('hidden');

    Main.hideModals();

    target.closest('form').querySelector('.change-booking-date').textContent    = 'Change';

    target.closest('form').querySelector('.change-booking-date').classList.remove('hidden');

    reset(modal, false, false);
}

function daySelected(target){ 
    let modal   = target.closest('.modal');

    if(modal == null){
        return;
    }

    let roomWrapper = target.closest('.room-wrapper');

    // remove previous selection
    if(roomWrapper.querySelector('.calendar.day.startdate') != null && roomWrapper.querySelector('.calendar.day.enddate') != null){
        let onlyEnd = false;
        if(target.matches('.inbetween')){
            onlyEnd = true;
        }
        
        reset(modal, onlyEnd);
    }

    // start selection
    if(roomWrapper.querySelector('.calendar.day.startdate') == null){
        // the selected cell is the first day of an already booked period
        if(target.matches('.first-day')){
            Main.displayMessage('You can only select this day as the last day of your stay!', 'error');
            return;
        }

        modal.querySelector('.booking-startdate').value               = target.dataset.date;
        modal.querySelector('.booking-startdate').dataset.isodate     = target.dataset.isodate;
        target.classList.add('startdate');
        roomWrapper.querySelectorAll('.booking-date-label-wrapper.disabled').forEach(el=>el.classList.remove('disabled'));

        // do not allow any date before the startdate to be the enddate
        let dts     = roomWrapper.querySelectorAll('dt.calendar.day:not(.head, .unavailable)');
        let skip    = false;
        for (let i = 0; i < dts.length; ++i) {
            // all dates after the booked date are available
            if(dts[i] == target){
                skip = true;
            // until we encounter another booked date
            }else if(skip && dts[i].matches('.booked:not(.available)')){
                skip = false;
            }

            if(!skip){
                dts[i].classList.add('unavailable');
            }
        }
    }else{
        // the selected cell is the first day of an already booked period
        if(target.matches('.last-day')){
            Main.displayMessage('You can only select this day as the first day of your stay!', 'error');
            return;
        }

        // the selected cell is also the startdate
        if(target.closest('.startdate') != null){
            Main.displayMessage('End date cannot be the same as the startdate!', 'error');
            return;
        }

        // store enddate
        modal.querySelector('.booking-enddate').value               = target.dataset.date;
        modal.querySelector('.booking-enddate').dataset.isodate     = target.dataset.isodate;
        target.classList.add('enddate');

        // color the dates between start and end
        let dts     = roomWrapper.querySelectorAll('dt.calendar.day:not(.head)');
        let skip    = true;
        for (let i = 0; i < dts.length; ++i) {
            
            // until we encounter another booked date
            if(dts[i] == target){
                break;
            }

            if(!skip){
                dts[i].classList.add('inbetween');
            }

            // all dates after the booked date are available
            if(dts[i].matches('.startdate')){
                skip = false;
            }
        }

        // make other dates available again
        //roomWrapper.querySelectorAll('.available.booked:not(.enddate, .startdate, .inbetween)').forEach(dt=>dt.classList.remove('booked'));

        //check if overlapping an existing booking
        if(target.closest('.calendar.table').querySelectorAll('.inbetween.first-day, .inbetween.last-day').length > 0){
            Main.displayMessage('You can not book this period as it is overlapping an existing booking!', 'error');

            reset(modal);

            return;
        }

        modal.querySelectorAll('.actions .action.disabled').forEach(el=>el.classList.remove('disabled'));
    }
}

function roomSelected(target){

    let modal   = target.closest('.modal');

    if(modal == null){
        modal = target.closest('.booking.overview'); // when viewing the results
    }

    if(modal == null){
        return;
    }

    // show date warning
    modal.querySelectorAll(`.booking-date-wrapper.hidden`).forEach(el=>el.classList.remove('hidden'));

    // show month navigator
    modal.querySelectorAll(`.navigators.hidden`).forEach(el=>el.classList.remove('hidden'));

    // Show the selected room
    modal.querySelectorAll(`[data-room="${target.value}"]`).forEach(el=>el.classList.toggle('hidden'));

    // Show selected room description
    modal.querySelectorAll(`.room-description[data-room-name="${target.value}"]`).forEach(el=>el.classList.toggle('hidden'));
    
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("Bookings.js loaded");

    // show booking date selector
    document.querySelectorAll(`.booking-subject-selector`).forEach(el=>el.addEventListener(`change`, (ev) => {
        ev.stopImmediatePropagation();
        Main.showModal(document.querySelector(`[name="${ev.target.value}-modal"]`));
    }));

    // show booking calendar
    document.querySelectorAll(`.admin-booking-subject-selector`).forEach(el => el.addEventListener(`change`, (ev) => {
        ev.stopImmediatePropagation();
        document.querySelector(`.bookings-wrap[data-subject="${ev.target.value}"]`).classList.toggle('hidden');
    }));

    document.querySelectorAll(".tables-wrapper").forEach(wrapper => {
        let offset	= wrapper.getBoundingClientRect().x;
        wrapper.style.marginLeft = `-${offset}px`;
    });

    document.querySelectorAll('.booking-detail tr.actions').forEach(row=>row.addEventListener('submissionArchived', ev => {
        let wrapper     = ev.target.closest('.booking-detail-wrapper');
        let bookingId   = wrapper.dataset.bookingId;

        // mark dates as available again
        document.querySelectorAll(`.calendar.day.booked[data-booking-id="${bookingId}"]`).forEach(el=>{
            el.classList.remove('booked');
            el.classList.add('available');
        });

        // remove details
        wrapper.remove();
        
        Main.displayMessage('Succesfully archived');
    }));

    document.querySelectorAll(`.lazy-post`).forEach(div => loadPost(div));
});

document.addEventListener('click', (ev) => {
    let target  = ev.target;
    let modal   = target.closest('.modal');

    if(target.closest('dt') != null){
        target  = target.closest('dt');
    }

    if(target.matches('.action.reset') ){
        reset(modal, false, false);
    }else if(target.matches('.available.unavailable')){
        reset(modal);
    }else if(target.matches('.prevnext')){
        getMonth(target);
    }else if(target.matches('.change-booking-date, [name="booking-startdate"], [name="booking-enddate"]')){
        changeBookingData(target);
    }else if(target.matches('.action.confirm')){
        storeDates(target);
    }else if(target.matches('.booking-subject-selector.overlap')){
        if(target.value == 'yes'){
            target.closest(`.form-label`).querySelector(`.min-bookking-gap-time`).classList.add('hidden');
        }else{
            target.closest(`.form-label`).querySelector(`.min-bookking-gap-time`).classList.remove('hidden');
        }
    }
    
    if(target.matches('.bookings-wrap .available:not(.unavailable)') && modal != null){
        daySelected(target);
    }else if(target.matches('.form.table-wrapper .booked')){
        // Hide others
        target.closest('.bookings-wrap').querySelectorAll(`.booking-detail-wrapper:not(.hidden)`).forEach(el=>el.classList.add('hidden'));
        
        // Show the details
        target.closest('.bookings-wrap').querySelectorAll(`.booking-detail-wrapper[data-booking-id="${target.dataset.bookingId}"]`).forEach(el=>{
            el.classList.remove('hidden');
            el.scrollIntoView({block: "center"});
        });
    }else if(target.matches('.button.approve')){
        approve(target);
    }else if(target.matches('.button.delete')){
        remove(target);
    }else if(target.matches('.room-selector')){
        roomSelected(target);
    }else if(target.matches('.confirmed-roles-switcher')){
        target.closest('.form-label').querySelectorAll('.confirmed-roles-wrapper').forEach(el=>el.classList.toggle('hidden'));
    }else if(target.matches(`.room-details`)){
        target.closest(`.input-wrapper`).querySelector(`[name="${target.dataset.target}"]`).classList.toggle('hidden');
    }else if(target.matches(`.location-details`)){
        target.closest(`.input-wrapper`).querySelector(`[name="location-details-modal"]`).classList.toggle('hidden');
    }

    
});

document.addEventListener('change', (ev) => {
    let target  = ev.target;

    if(target.name.includes('default-booking-state')){
        if(target.value == 'pending' && target.checked){
            target.closest('.formfield.form-label').querySelectorAll('button.confirmed-roles-switcher.hidden').forEach(but=>but.classList.remove('hidden'));
        }else{
            target.closest('.formfield.form-label').querySelectorAll('button.confirmed-roles-switcher:not(.hidden)').forEach(but=>but.classList.add('hidden'));
        }
    }else if(target.name.includes('amount')){
        let tabContent      = target.closest('.tabcontent');
        if(tabContent == null){
            return;
        }

        let numberingType   = tabContent.querySelector(`.room-numbering`);
        let rooms           = tabContent.querySelector(`.rooms`);

        if(target.value < 2){
            numberingType.classList.add('hidden');
            rooms.classList.add('hidden');
        }else{
            numberingType.classList.remove('hidden');
            rooms.classList.remove('hidden');
        }
    }else if(target.matches('.numbering-type')){
        let tabContent      = target.closest('.tabcontent');
        if(tabContent == null){
            return;
        }

        tabContent.querySelectorAll('.roomname').forEach(el =>{
            if(target.value == 'custom'){
                el.classList.remove('hidden');
            }else{
                el.classList.add('hidden');
            }
        });
    }else{
        return;
    }

    ev.stopImmediatePropagation();
});

document.addEventListener('input', (ev) => {
    let target  = ev.target;
    if(target.matches(`.subject-name`)){
        ev.stopImmediatePropagation();

        let tabId   = target.closest(`.tabcontent`).id;
        document.querySelector(`[data-target="${tabId}"`).textContent   = target.value;
    }
});