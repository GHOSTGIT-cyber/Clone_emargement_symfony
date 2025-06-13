document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const userRole = calendarEl.dataset.role || 'apprenant';

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            today: 'Aujourd\'hui',
            month: 'Mois',
            week: 'Semaine',
            day: 'Jour',
            list: 'Liste'
        },
        slotMinTime: '07:00:00',
        slotMaxTime: '20:00:00',
        allDaySlot: false,
        locale: 'fr',
        firstDay: 1,

        events: '/calendrier/events',

        eventClassNames: function (arg) {
            return [arg.event.extendedProps.role];
        },

        eventDidMount: function (info) {
            let tooltip = '';

            if (userRole === 'apprenant') {
                const salle = info.event.extendedProps.salle || 'Salle à définir';
                const justification = info.event.extendedProps.justification || '';
                tooltip = `${info.event.title}\nSalle : ${salle}`;
                if (justification) {
                    tooltip += `\n${justification}`;
                }
            }

            if (userRole === 'formateur') {
                const salle = info.event.extendedProps.salle || 'Salle à définir';
                tooltip = `${info.event.title}\nSalle : ${salle}`;
            }

            if (userRole === 'admin') {
                const salle = info.event.extendedProps.salle || 'Salle à définir';
                const formateur = info.event.extendedProps.formateur || 'Non précisé';
                tooltip = `${info.event.title}\nSalle : ${salle}\nFormateur : ${formateur}`;
            }

            if (tooltip) {
                info.el.setAttribute('title', tooltip);
            }
        },

        eventClick: function (info) {
            const modal = document.getElementById('eventModal');
            const title = document.getElementById('modalTitle');
            const salle = document.getElementById('modalSalle');
            const formateur = document.getElementById('modalFormateur');
            const justification = document.getElementById('modalJustification');

            const props = info.event.extendedProps;

            title.textContent = info.event.title;
            salle.textContent = props.salle || 'Non défini';
            formateur.textContent = props.formateur || 'Non précisé';
            justification.textContent = props.justification || 'Aucune';

            modal.style.display = 'block';

            document.querySelector('#eventModal .close').onclick = function () {
                modal.style.display = 'none';
            };

            window.onclick = function (event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            };
        }
    });

    calendar.render();
});