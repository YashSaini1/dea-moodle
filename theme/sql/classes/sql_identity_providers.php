<?php

namespace theme_sql;

/**
 * Class sql_identity_providers
 *
 * @package theme_sql
 */
class sql_identity_providers
{
    public $identityproviders;

    /**
     * sql_identity_providers constructor.
     */
    public function __construct() {

        $authsequence = get_enabled_auth_plugins();
        $this->identityproviders = \auth_plugin_base::get_identity_providers($authsequence);
    }

    /**
     * @return string
     */
    function render() {
        global $OUTPUT;

        if (!$this->hasIdentityProviders())
            return '';

        $identityproviders = [];
        foreach ($this->identityproviders as $identity_provider) {
            $identityproviders[] = [
                'url' => $identity_provider['url']->out(false),
                'iconurl' => $OUTPUT->pix_icon("auth/{$identity_provider['name']}", $identity_provider['name'], 'theme_sql') ? $OUTPUT->pix_icon("auth/{$identity_provider['name']}", $identity_provider['name'], 'theme_sql') : $identity_provider['iconurl'],
                'name' => $identity_provider['name']
            ];
        }
        $data = [
            'identityproviders' => $identityproviders,
        ];
        return $OUTPUT->render_from_template('theme_sql/sql_identity_providers', $data);
    }

    /**
     * @return bool
     */
    function hasIdentityProviders() {
        return !is_null($this->identityproviders);
    }
}