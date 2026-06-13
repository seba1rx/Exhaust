/**
 * Engeen — SweetAlert2 Dialog Driver
 *
 * Full coverage of all Commands.php dialog and toast commands.
 * Requires SweetAlert2 v11+ loaded before this script.
 *
 * Usage:
 *   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 *   <script src="engeen.js"></script>
 *   <script src="drivers/engeen-swal2-driver.js"></script>
 *   <script>Engeen.setDialogDriver(EngeenSwal2Driver);</script>
 */
const EngeenSwal2Driver = {

    /**
     * Show a SweetAlert2 modal dialog.
     *
     * @param {Object} args
     * @param {string}  args.type    - icon type: info | success | warning | error | question
     * @param {string}  [args.title]
     * @param {string}  [args.text]
     * @param {string}  [args.html]  - overrides text if set
     * @param {Object}  [args.buttons]
     * @param {Object}  [args.buttons.confirm] - { text, class, callback }
     * @param {Object}  [args.buttons.deny]    - { text, class, callback }
     * @param {Object}  [args.buttons.cancel]  - { text, class, callback }
     * @param {Object}  [args.timer]            - { time (ms), callback }
     */
    dialog: ({ type, title, text, html, buttons, timer }) => {
        let showConfirmButton = false;
        let showDenyButton    = false;
        let showCancelButton  = false;

        let confirmButtonClass = null, denyButtonClass = null, cancelButtonClass = null;
        let confirmCallback    = null, denyCallback    = null, cancelCallback    = null;
        let confirmText        = null, denyText        = null, cancelText        = null;

        if (buttons) {
            if (buttons.confirm) {
                showConfirmButton  = true;
                confirmText        = buttons.confirm.text     ?? null;
                confirmButtonClass = buttons.confirm.class    ?? null;
                confirmCallback    = buttons.confirm.callback ?? null;
            }
            if (buttons.deny) {
                showDenyButton  = true;
                denyText        = buttons.deny.text     ?? null;
                denyButtonClass = buttons.deny.class    ?? null;
                denyCallback    = buttons.deny.callback ?? null;
            }
            if (buttons.cancel) {
                showCancelButton  = true;
                cancelText        = buttons.cancel.text     ?? null;
                cancelButtonClass = buttons.cancel.class    ?? null;
                cancelCallback    = buttons.cancel.callback ?? null;
            }
        } else {
            showConfirmButton = true;
            confirmText       = 'Ok';
        }

        let time             = null;
        let timerProgressBar = false;
        let timerCallback    = null;

        if (timer) {
            timerProgressBar = true;
            time             = timer.time     ?? 1000;
            timerCallback    = timer.callback ?? null;
        }

        Swal.fire({
            icon:  type,
            title: title ?? null,
            text,
            html:  html ?? null,
            draggable:        true,
            allowOutsideClick: false,
            showCloseButton:   false,
            timer:             time,
            timerProgressBar,
            showConfirmButton,
            showDenyButton,
            showCancelButton,
            confirmButtonText: confirmText,
            denyButtonText:    denyText,
            cancelButtonText:  cancelText,
            customClass: {
                confirmButton: confirmButtonClass,
                denyButton:    denyButtonClass,
                cancelButton:  cancelButtonClass,
            },
        }).then((result) => {
            if      (result.isConfirmed && confirmCallback)                              eval(confirmCallback);
            else if (result.isDenied    && denyCallback)                                 eval(denyCallback);
            else if (result.dismiss === Swal.DismissReason.cancel  && cancelCallback)    eval(cancelCallback);
            else if (result.dismiss === Swal.DismissReason.timer   && timerCallback)     eval(timerCallback);
        });
    },

    /**
     * Show a SweetAlert2 toast notification.
     *
     * @param {Object} args
     * @param {string} args.type     - icon: info | success | warning | error | question
     * @param {string} args.title    - message text
     * @param {number} [args.duration]  - ms, default 3000
     * @param {string} [args.position]  - default 'top-end'
     */
    toast: ({ type, title, duration = 3000, position = 'top-end' }) => {
        const allowed = ['top','top-start','top-end','center','center-start','center-end','bottom','bottom-start','bottom-end'];
        if (!allowed.includes(position)) position = 'top-end';
        if (duration < 1000) duration = 1000;

        Swal.mixin({
            toast:             true,
            position,
            showConfirmButton: false,
            timer:             duration,
            timerProgressBar:  true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            },
        }).fire({ icon: type, title });
    },

    /**
     * Close any visible SweetAlert2 dialog.
     */
    close: () => {
        Swal.close();
    },

    /**
     * Show a loading dialog (spinner, no buttons).
     * @param {string} [title]
     */
    loading: (title) => {
        Swal.fire({
            title:             title ?? 'Procesando solicitud',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen:           () => { Swal.showLoading(); },
        });
    },
};
