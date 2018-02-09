<?php

namespace Drupal\badbehavior\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Configuration form definition for the salutation message.
 */
class BadbehaviorConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['badbehavior.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'badbehavior_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('badbehavior.settings');
    $form['standard'] = array(
      '#type' => 'fieldset',
      '#title' => t('Standard Settings'),
    );
    $mail = $config->get('mail');
    if (empty($mail)) {
      $system_config = $this->config('system.site');
      $mail = $system_config->get('mail');
    }
    $form['standard']['mail'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Administrator Email'),
      '#default_value' => $mail,
      '#description' => $this->t('E-mail address for blocked users to contact in order to gain access.'),
      '#required' => TRUE,
    );
    $form['standard']['logging'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable logging'),
      '#default_value' => $config->get('logging'),
    );
    $form['standard']['verbose'] = array(
      '#type' => 'select',
      '#title' => $this->t('Logging mode'),
      '#options' => array(
        0 => t('Standard'),
        1 => t('Verbose'),
      ),
      '#default_value' => $config->get('verbose'),
      '#states' => array(
        'visible' => array(
          ':input[name="logging"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['standard']['log_timeformat'] = array(
      '#type' => 'select',
      '#title' => $this->t('Logging Time Display Format'),
      '#options' => array(
        '24' => t('24-hour'),
        '12' => t('12-hour'),
      ),
      '#default_value' => $config->get('log_timeformat'),
      '#description' => $this->t('Set the <a href="/admin/reports/badbehavior">Bad Behavior Log</a> time display in 12- or 24-hour format.'),
      '#states' => array(
        'visible' => array(
          ':input[name="logging"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['standard']['offsite_forms'] = array(
      '#type' => 'select',
      '#title' => $this->t('Offsite Forms'),
      '#options' => array(
        0 => $this->t('Deny'),
        1 => $this->t('Allow'),
      ),
      '#default_value' => $config->get('offsite_forms'),
      '#description' => t('Bad Behavior normally prevents your site from receiving data posted from forms on other web sites. This prevents spammers from, e.g., using a Google cached version of your web site to send you spam. There are certain reasons why you might want to enable this, such as when using remote login apps to allow users to login to your website from offsite locations.'),
    );
    $form['standard']['strict'] = array(
      '#type' => 'checkbox',
      '#title' => 'Enable strict mode',
      '#default_value' => $config->get('strict'),
      '#description' => t('Bad Behavior operates in two blocking modes: normal and strict. When strict mode is enabled, some additional checks for buggy software which have been spam sources are enabled, but occasional legitimate users using the same software (usually corporate or government users using very old software) may be blocked as well. It is up to you whether you want to have the government reading your blog, or keep away more spammers.'),
    );

    // Project Honey Pot support.
    $form['httpbl'] = array(
      '#type' => 'fieldset',
      '#title' => t('Project Honey Pot Support'),
    );
    $form['httpbl']['key'] = array(
      '#type' => 'textfield',
      '#title' => t('Your http:BL Access Key'),
      '#default_value' => $config->get('httpbl.key'),
      '#maxlength' => 12,
      '#size' => 12,
      '#description' => $this->t("To enable Bad Behavior's http:BL features you must have an <a href=\"@httpbl-url\" target=\"_blank\">http:BL Access Key</a>.", array('@httpbl-url' => 'http://www.projecthoneypot.org/httpbl_configure.php')),
    );
    $form['httpbl']['threat'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('http:BL Threat Level'),
      '#default_value' => $config->get('httpbl.threat'),
      '#maxlength' => 3,
      '#size' => 3,
      '#description' => t("This number provides a measure of how suspicious an IP address is, based on activity observed at Project Honey Pot. Bad Behavior will block requests with a threat level equal or higher to this setting. Project Honey Pot has <a href=\"@httpblthreat-url\" target=\"_blank\">more information</a> on this parameter.", array('@httpblthreat-url' => 'http://www.projecthoneypot.org/threat_info.php')),
    );
    $form['httpbl']['age'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('http:BL Maximum Threat Age'),
      '#default_value' => $config->get('httpbl.age'),
      '#maxlength' => 2,
      '#size' => 2,
      '#description' => $this->t('This is the number of days since suspicious activity was last observed from an IP address by Project Honey Pot. Bad Behavior will block requests with a maximum age equal to or less than this setting.'),
    );
    $form['httpbl']['quicklink'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Project Honey Pot QuickLink'),
      '#default_value' => $config->get('httpbl.quicklink'),
      '#maxlength' => 255,
      '#description' => $this->t("To include a hidden Project Honey Pot QuickLink to your website to help the anti-spam community trap unsuspecting spam bots, enter your QuickLink URL (with the 'http://') in this field. Create a free Project Honey Pot account and find <a href=\"@httpblquicklinks-url\" target=\"_blank\">more information</a> about QuickLinks.", array('@httpblquicklinks-url' => 'http://www.projecthoneypot.org/manage_quicklink.php')),
    );
    $form['httpbl']['quicklinktext'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Project Honey Pot QuickLink Hidden Text'),
      '#default_value' => $config->get('httpbl.quicklinktext'),
      '#maxlength' => 99,
      '#description' => $this->t("If you entered a QuickLink, put a plain text phrase or word in this field that could be a relevant link title for your website (to fool the spam-bots). See <a href=\"@httpblquicklinks-url\" target=\"_blank\">more information here</a> about QuickLinks text. (This field will not be used if there is no QuickLinks URL defined in the Project Honey Pot QuickLink field above)", array('@httpblquicklinks-url' => 'http://www.projecthoneypot.org/manage_quicklink.php')),
    );

    // Reverse proxy and load balancer support.
    $form['reverse_proxy'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Reverse Proxy/Load Balancer Support (Advanced Option)'),
      '#description' => $this->t('Set this option <em>only</em> when using Bad Behavior behind a reverse proxy or load balancer. See the README.txt for details.'),
    );
    $form['reverse_proxy']['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => 'Enable Reverse Proxy Support',
      '#default_value' => $config->get('reverse_proxy.enabled'),
      '#description' => $this->t('When enabled, Bad Behavior will assume it is receiving a connection from a reverse proxy, when a specific HTTP header is received. This option is enabled by default when you enable Drupal\'s built-in reverse_proxy option.'),
    );
    $form['reverse_proxy']['header'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reverse Proxy Header'),
      '#default_value' => $config->get('reverse_proxy.header'),
      '#maxlength' => 99,
      '#description' => $this->t('If you enabled the Reverse Proxy support, you may specify a header to use to help Bad Behavior identify the IP address of the proxy server.'),
      '#states' => array(
        'visible' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['reverse_proxy']['addresses'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Reverse Proxy Addresses'),
      '#default_value' => $config->get('reverse_proxy.addresses'),
      '#description' => $this->t('One per line'),
      '#states' => array(
        'visible' => array(
          ':input[name="enabled"]' => array('checked' => TRUE),
        ),
      ),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!valid_email_address($form_state->getValue('mail'))) {
      $form_state->setErrorByName('mail', $this->t('The e-mail address is not valid.'));
    }
    if (!UrlHelper::isValid($form_state->getValue('quicklink'), $absolute = TRUE)) {
      $form_state->setErrorByName('quicklink', $this->t('The Project Honey Pot QuickLink must be an absolute URL, starting with http:// or https://'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('badbehavior.settings')
      ->set('mail', $form_state->getValue('mail'))
      ->set('logging', $form_state->getValue('logging'))
      ->set('verbose', $form_state->getValue('verbose'))
      ->set('log_timeformat', $form_state->getValue('log_timeformat'))
      ->set('offsite_forms', $form_state->getValue('offsite_forms'))
      ->set('strict', $form_state->getValue('strict'))
      ->set('httpbl.key', $form_state->getValue('key'))
      ->set('httpbl.threat', $form_state->getValue('threat'))
      ->set('httpbl.age', $form_state->getValue('age'))
      ->set('httpbl.quicklink', $form_state->getValue('quicklink'))
      ->set('httpbl.quicklinktext', $form_state->getValue('quicklinktext'))
      ->set('reverse_proxy.enabled', $form_state->getValue('enabled'))
      ->set('reverse_proxy.header', $form_state->getValue('header'))
      ->set('reverse_proxy.addresses', $form_state->getValue('addresses'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}