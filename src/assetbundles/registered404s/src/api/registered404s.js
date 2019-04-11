/* global Craft */

import axios from 'axios'

export default {
    /**
     * Get cart.
     */
    get404s(params) {
        axios.defaults.headers.common['X-CSRF-Token'] = Craft.csrfTokenValue
        return axios.post(Craft.getActionUrl('vredirect/catch-all/get-filtered'), params)
    },

    delete404s(rows) {
        axios.defaults.headers.common['X-CSRF-Token'] = Craft.csrfTokenValue
        let ids = rows.map(({ id }) => id)
        return axios.post(Craft.getActionUrl('vredirect/catch-all/delete'), ids)
    },

    ignore404s(rows) {
        axios.defaults.headers.common['X-CSRF-Token'] = Craft.csrfTokenValue
        let ids = rows.map(({ id }) => id)
        return axios.post(Craft.getActionUrl('vredirect/catch-all/ignore'), ids)
    },

    unIgnore404s(rows) {
        axios.defaults.headers.common['X-CSRF-Token'] = Craft.csrfTokenValue
        let ids = rows.map(({ id }) => id)
        return axios.post(Craft.getActionUrl('vredirect/catch-all/un-ignore'), ids)
    }
}
