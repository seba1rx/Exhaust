/**
 * Engeen — Notiflix Dialog Driver
 *
 * Requires Notiflix v3+ loaded before this script.
 *
 * Usage:
 *   <script src="https://cdn.jsdelivr.net/npm/notiflix@3/build/notiflix-aio-bundle.min.js"></script>
 *   <script src="engeen.js"></script>
 *   <script src="drivers/engeen-notiflix-driver.js"></script>
 *   <script>Engeen.setDialogDriver(EngeenNotiflixDriver);</script>
 *
 * Coverage vs Commands.php full spec:
 *
 *   Dialogs:
 *     ✅ info, success, warning, error   (via Notiflix.Report)
 *     ⚠️  question                        (mapped to info — Notiflix has no question icon)
 *     ✅  confirm + cancel buttons        (via Notiflix.Confirm)
 *     ⚠️  deny button                    (not supported — ignored)
 *     ⚠️  html content                   (HTML tags stripped — Notiflix uses plain text)
 *     ⚠️  timer                          (not supported — ignored)
 *
 *   Toasts (Notiflix.Notify):
 *     ✅ info, success, warning, error
 *     ⚠️  question                        (mapped to info)
 *     ✅  duration
 *     ✅  position
 */
const EngeenNotiflixDriver = {

    /**
     * Throws a descriptive error if the Notiflix global is not available.
     * Catches CDN load failures before they produce a cryptic ReferenceError.
     *
     * @throws {Error}
     */
    _requireNotiflix: () => {
        if (typeof Notiflix === 'undefined') {
            throw new Error(
                'EngeenNotiflixDriver: Notiflix is not loaded. ' +
                'Ensure the Notiflix script is included before this driver.'
            );
        }
    },

    _typeMap: {
        info:     'info',
        success:  'success',
        warning:  'warning',
        error:    'failure',
        question: 'info',
    },

    _positionMap: {
        'top':          'center-top',
        'top-start':    'left-top',
        'top-end':      'right-top',
        'center':       'center',
        'center-start': 'left',
        'center-end':   'right',
        'bottom':       'center-bottom',
        'bottom-start': 'left-bottom',
        'bottom-end':   'right-bottom',
    },

    /**
     * Show a Notiflix dialog.
     *
     * When both confirm and cancel buttons are provided, uses Notiflix.Confirm.
     * Otherwise uses Notiflix.Report (single-button dialog).
     * The deny button is not supported by Notiflix and is ignored.
     * HTML content is stripped to plain text.
     *
     * @param {Object} args
     * @param {string}  args.type
     * @param {string}  [args.title]
     * @param {string}  [args.text]
     * @param {string}  [args.html]
     * @param {Object}  [args.buttons]
     */
    dialog: ({ type, title, text, html, buttons }) => {
        EngeenNotiflixDriver._requireNotiflix();
        const nxType  = EngeenNotiflixDriver._typeMap[type] ?? 'info';
        const content = text ?? (html ? html.replace(/<[^>]*>/g, '') : '');
        const heading = title ?? '';

        if (buttons?.confirm && buttons?.cancel) {
            Notiflix.Confirm.show(
                heading,
                content,
                buttons.confirm.text ?? 'Ok',
                buttons.cancel.text  ?? 'Cancelar',
                () => { if (buttons.confirm?.callback) eval(buttons.confirm.callback); },
                () => { if (buttons.cancel?.callback)  eval(buttons.cancel.callback); }
            );
            return;
        }

        const btnText     = buttons?.confirm?.text ?? 'Ok';
        const btnCallback = buttons?.confirm?.callback
            ? () => eval(buttons.confirm.callback)
            : () => {};

        Notiflix.Report[nxType](heading, content, btnText, btnCallback);
    },

    /**
     * Show a Notiflix Notify toast.
     *
     * @param {Object} args
     * @param {string} args.type
     * @param {string} args.title
     * @param {number} [args.duration]
     * @param {string} [args.position]
     */
    toast: ({ type, title, duration = 3000, position = 'top-end' }) => {
        EngeenNotiflixDriver._requireNotiflix();
        const nxType     = EngeenNotiflixDriver._typeMap[type] ?? 'info';
        const nxPosition = EngeenNotiflixDriver._positionMap[position] ?? 'right-top';
        if (duration < 1000) duration = 1000;

        Notiflix.Notify[nxType](title, {
            timeout:  duration,
            position: nxPosition,
        });
    },

    /**
     * Remove any active Notiflix Loading overlay.
     * Report/Confirm dialogs close themselves on button click.
     */
    close: () => {
        EngeenNotiflixDriver._requireNotiflix();
        Notiflix.Loading.remove();
    },

    /**
     * Show a Notiflix Loading overlay.
     * @param {string} [title]
     */
    loading: (title) => {
        EngeenNotiflixDriver._requireNotiflix();
        Notiflix.Loading.standard(title ?? 'Procesando solicitud...');
    },
};
