import './settings.js'
import './user.js'
import './role.js'
import './nav.js'

/**
 * @typedef {Object} FlashMessages
 * @property {string|null} success
 * @property {string|null} error
 * @property {string|null} warning
 */

/**
 * @typedef {Object} PageProps
 * @property {{ user: (User|null) }} auth
 * @property {ClinicSettings} settings
 * @property {FlashMessages} flash
 * @property {Record<string, string>} [errors]
 */

export {}
