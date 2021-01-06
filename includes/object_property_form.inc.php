<?php

function uofm_redirect_object_property_form($form, &$form_state, AbstractObject $object) {
  $internal_url = uofm_redirect_object_get_url($object);
  $form = [
    'redirect' => [
      '#type' => 'textfield',
      '#title' => t('Redirect URL'),
      '#required' => true,
      '#description' => t('The URL to present as the location of this resource.'),
      '#default_value' => (isset($form_state['values']['redirect']) ? $form_state['values']['redirect'] : $internal_url),
    ],
    'submit' => [
      '#type' => 'submit',
      '#value' => t("Update"),
    ],
  ];
  return $form;
}

function uofm_redirect_object_property_form_submit($form, &$form_state) {
  $object = menu_get_object('islandora_object', 2);
  $url = $form_state['values']['redirect'];
  $rels = $object['RELS-INT'];
  $rels->relationships->remove(UOFM_REDIRECT_PREDICATE_NS, UOFM_REDIRECT_PREDICATE_TAG, NULL);
  $rels->relationships->add(UOFM_REDIRECT_PREDICATE_NS, UOFM_REDIRECT_PREDICATE_TAG, $url, RELS_TYPE_FULL_URI);
}