/* global Craft */

import axios from 'axios'

export default {
    /**
     * Get cart.
     */
    get404s(params) {
        axios.defaults.headers.common['X-CSRF-Token'] = Craft.csrfTokenValue
        return axios.post(Craft.getActionUrl('vredirect/catch-all/get-filtered'), {
            data: params
        })
    },
}
