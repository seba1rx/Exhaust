/**
 * Engeen Client
 *
 * Zero external dependencies. Requires a dialog driver registered via
 * Engeen.setDialogDriver() before using dialog or toast functionality.
 *
 * Available drivers (include one after this script):
 *   + drivers/engeen-swal2-driver.js     (SweetAlert2 v11+)
 *   + drivers/engeen-notiflix-driver.js  (Notiflix v3+)
 *
 * Requests are sent using the native fetch() API with the header
 * X-Requested-With: fetch, which the backend detects as RequestType::Fetch.
 *
 * Optional global: set DEBUG_FRONTEND = true to enable verbose console output.
 */
const Engeen = {

    _dialogDriver: null,

    /**
     * Register a dialog/toast driver.
     * @param {Object} driver - must implement: dialog(), toast(), close(), loading()
     */
    setDialogDriver: (driver) => {
        Engeen._dialogDriver = driver;
    },

    form: {
        /**
         * Serialize a form's values into a plain object.
         * @param {string} form_id
         * @returns {Object}
         */
        getData: (form_id) => {
            const form = document.getElementById(form_id);
            const data = {};
            new FormData(form).forEach((value, key) => { data[key] = value; });
            return data;
        },
    },

    /**
     * Navigate to a route or URL.
     * @param {string} path
     */
    route: (path) => {
        try {
            window.location.replace(path);
        } catch (e) {
            Engeen.console.error(e.message, e.stack);
        }
    },

    /**
     * Alias for Engeen.route.
     * @param {string} url
     */
    redirect: (url) => {
        try {
            window.location.replace(url);
        } catch (e) {
            Engeen.console.error(e.message, e.stack);
        }
    },

    request: {
        /** @param {Object} arg */
        post:   (arg) => Engeen.request.send(arg, 'POST'),
        /** @param {Object} arg */
        get:    (arg) => Engeen.request.send(arg, 'GET'),
        /** @param {Object} arg */
        put:    (arg) => Engeen.request.send(arg, 'PUT'),
        /** @param {Object} arg */
        delete: (arg) => Engeen.request.send(arg, 'DELETE'),

        /**
         * Send a request to the server and process the command response.
         *
         * @param {Object} arg
         * @param {string}  arg.url           - target route
         * @param {Object}  [arg.payload]     - data to send
         * @param {string|boolean} [arg.showLoading] - show loading dialog; pass true or a custom title string
         * @param {string}  [arg.before_script] - JS evaluated before the request is sent
         * @param {string}  [arg.done_script]   - JS evaluated after the response is received
         * @param {string}  method
         * @returns {Promise<void>}
         */
        send: async (arg, method = 'POST') => {
            const d = Boolean(typeof DEBUG_FRONTEND !== 'undefined' ? DEBUG_FRONTEND : false);

            if (d) Engeen.console.debug(`sending ${method}`, arg.payload);

            if (typeof arg.showLoading !== 'undefined') {
                const title = (arg.showLoading === '' || arg.showLoading === null || arg.showLoading === true)
                    ? 'Procesando solicitud'
                    : arg.showLoading;
                Engeen._dialogDriver?.loading(title);
            }

            if (typeof arg.before_script !== 'undefined') {
                if (d) Engeen.console.debug('eval before_script');
                try { eval(arg.before_script); } catch (e) { Engeen.console.error(e.message, e.stack); }
            }

            const headers = { 'X-Requested-With': 'fetch' };
            let url  = arg.url ?? '/';
            let body;

            if (method === 'GET') {
                const qs = new URLSearchParams(arg.payload ?? {}).toString();
                if (qs) url += (url.includes('?') ? '&' : '?') + qs;
            } else {
                headers['Content-Type'] = 'application/json';
                body = JSON.stringify(arg.payload ?? {});
            }

            try {
                const res  = await fetch(url, { method, headers, body });
                const data = await res.json();
                if (d) Engeen.console.debug('response received', data);
                Engeen.executeCommands(data);
            } catch (e) {
                Engeen.console.error('Request failed', e.message);
                Engeen.terminateDialog();
            }

            if (typeof arg.done_script !== 'undefined') {
                if (d) Engeen.console.debug('eval done_script');
                try { eval(arg.done_script); } catch (e) { Engeen.console.error(e.message, e.stack); }
            }
        },
    },

    /**
     * Process the command object received from the server.
     * @param {Object} response
     */
    executeCommands: (response) => {
        const d = Boolean(typeof DEBUG_FRONTEND !== 'undefined' ? DEBUG_FRONTEND : false);
        if (d) Engeen.console.debug('handling response', response);

        let commands = 0;

        // html — assign innerHTML to elements by id
        if (typeof response.html !== 'undefined') {
            commands++;
            Object.entries(response.html).forEach(([node_id, content]) => {
                if (d) Engeen.console.debug('html → #' + node_id, content);
                const el = document.getElementById(node_id);
                if (el) {
                    el.innerHTML = content;
                } else {
                    Engeen.console.warning(`html command: element #${node_id} not found in DOM`);
                }
            });
        }

        // script — eval arbitrary JS
        if (typeof response.script !== 'undefined') {
            commands++;
            try {
                if (d) Engeen.console.debug('eval response.script', response.script);
                eval(response.script);
            } catch (e) {
                Engeen.console.error('Error executing script command', e.message);
            }
        }

        // console_log — native console.log
        if (typeof response.console_log !== 'undefined') {
            commands++;
            console.log(response.console_log);
        }

        // log — typed colored console output
        if (typeof response.log !== 'undefined') {
            for (const [logType, entry] of Object.entries(response.log)) {
                commands++;
                try {
                    const fn = Engeen.console[logType];
                    if (typeof fn === 'function') {
                        typeof entry.details !== 'undefined'
                            ? fn(entry.text, entry.details)
                            : fn(entry.text);
                    }
                } catch (e) {
                    Engeen.console.error(`Error processing log (${logType})`, e.message);
                }
            }
        }

        // dialog — delegate to driver
        if (typeof response.dialog !== 'undefined') {
            if (!Engeen._dialogDriver) {
                Engeen.console.warning('dialog command received but no driver is registered — call Engeen.setDialogDriver()');
            } else {
                for (const [dialogType, conf] of Object.entries(response.dialog)) {
                    commands++;
                    try {
                        Engeen._dialogDriver.dialog({
                            type:    dialogType,
                            title:   conf.title,
                            text:    conf.text,
                            html:    conf.html,
                            buttons: conf.buttons,
                            timer:   conf.timer,
                        });
                    } catch (e) {
                        Engeen.console.error(`Error processing dialog (${dialogType})`, e.message);
                    }
                }
            }
        }

        // toast — delegate to driver
        if (typeof response.toast !== 'undefined') {
            if (!Engeen._dialogDriver) {
                Engeen.console.warning('toast command received but no driver is registered — call Engeen.setDialogDriver()');
            } else {
                for (const [toastType, conf] of Object.entries(response.toast)) {
                    commands++;
                    try {
                        Engeen._dialogDriver.toast({
                            type:     toastType,
                            title:    conf.title,
                            duration: conf.duration ?? 3000,
                            position: conf.position ?? 'top-end',
                        });
                    } catch (e) {
                        Engeen.console.error(`Error processing toast (${toastType})`, e.message);
                    }
                }
            }
        }

        // assignValue — assign a JS variable via eval
        if (typeof response.assignValue !== 'undefined') {
            if (d) Engeen.console.debug('eval assignValue');
            commands++;
            try {
                const [varName, varValue] = Object.entries(response.assignValue)[0];
                if (typeof varValue === 'object') {
                    eval(`${varName} = ${JSON.stringify(varValue)};`);
                } else if (typeof varValue === 'string') {
                    eval(`${varName} = '${varValue}';`);
                } else {
                    eval(`${varName} = ${varValue};`);
                }
            } catch (e) {
                Engeen.console.error('Error processing assignValue', e.message);
            }
        }

        if (commands === 0) Engeen.console.warning('No response commands were processed');

        Engeen.terminateDialog(response);
    },

    /**
     * Close any open loading dialog if the response does not include a dialog command.
     * @param {Object|null} response
     */
    terminateDialog: (response = null) => {
        if (!Engeen._dialogDriver) return;
        if (response === null || typeof response.dialog === 'undefined') {
            Engeen._dialogDriver.close();
        }
    },

    // Backward-compatible wrappers — delegate to the registered driver
    popDialog: {
        info:     (args) => Engeen._dialogDriver?.dialog({ type: 'info',     ...args }),
        success:  (args) => Engeen._dialogDriver?.dialog({ type: 'success',  ...args }),
        warning:  (args) => Engeen._dialogDriver?.dialog({ type: 'warning',  ...args }),
        error:    (args) => Engeen._dialogDriver?.dialog({ type: 'error',    ...args }),
        question: (args) => Engeen._dialogDriver?.dialog({ type: 'question', ...args }),
        any: (args) => args?.loading
            ? Engeen._dialogDriver?.loading(args.title)
            : Engeen._dialogDriver?.dialog(args),
    },
    popToast: {
        info:     (args) => Engeen._dialogDriver?.toast({ type: 'info',     ...args }),
        success:  (args) => Engeen._dialogDriver?.toast({ type: 'success',  ...args }),
        warning:  (args) => Engeen._dialogDriver?.toast({ type: 'warning',  ...args }),
        error:    (args) => Engeen._dialogDriver?.toast({ type: 'error',    ...args }),
        question: (args) => Engeen._dialogDriver?.toast({ type: 'question', ...args }),
        any:      (args) => Engeen._dialogDriver?.toast(args),
    },

    colors: {
        white:   '#FFFFFF', black:   '#000000', blue:    '#0000FF',
        green:   '#008000', yellow:  '#FFFF00', red:     '#FF0000',
        orange:  '#FFA500', pink:    '#FFC0CB', purple:  '#800080',
        grey:    '#808080', gold:    '#FFD700', lime:    '#00FF00',
        aqua:    '#00FFFF', beige:   '#F5F5DC', crimson: '#DC143C',
        fuchsia: '#FF00FF',
    },

    console: {
        info: (text, obj) => {
            const style = `background:${Engeen.colors.aqua};color:${Engeen.colors.black}`;
            obj !== undefined
                ? console.log(`%c INFO %c ${text}`, style, '', obj)
                : console.log(`%c INFO %c ${text}`, style, '');
        },
        error: (text, obj) => {
            const style = `background:${Engeen.colors.red};color:${Engeen.colors.white}`;
            obj !== undefined
                ? console.log(`%c ERROR %c ${text}`, style, '', obj)
                : console.log(`%c ERROR %c ${text}`, style, '');
        },
        debug: (text, obj) => {
            const style = `background:${Engeen.colors.gold};color:${Engeen.colors.black}`;
            obj !== undefined
                ? console.log(`%c DEBUG %c ${text}`, style, '', obj)
                : console.log(`%c DEBUG %c ${text}`, style, '');
        },
        warning: (text, obj) => {
            const style = `background:${Engeen.colors.orange};color:${Engeen.colors.black}`;
            obj !== undefined
                ? console.log(`%c WARNING %c ${text}`, style, '', obj)
                : console.log(`%c WARNING %c ${text}`, style, '');
        },
    },

    tab: {
        id: null,
        generateUuid: () => {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : ((r & 0x3) | 0x8);
                return v.toString(16);
            });
        },
        assignTabUuid: () => {
            let uid = window.sessionStorage.getItem('unique-tab-id');
            if (!uid || !window.name) {
                uid = Engeen.tab.generateUuid();
                window.sessionStorage.setItem('unique-tab-id', uid);
            }
            window.name   = uid;
            Engeen.tab.id = uid;
        },
    },
};
