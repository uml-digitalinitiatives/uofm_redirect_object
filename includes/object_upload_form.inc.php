<?php

/**
 * Defines a file upload form for a redirect object.
 *
 * @param array $form
 *   The Drupal form.
 * @param array $form_state
 *   The Drupal form state.
 *
 * @return array
 *   The Drupal form definition.
 */
function uofm_redirect_object_upload_form(array $form, array &$form_state) {
  return array(
    'title' => array(
      '#title' => t('Title'),
      '#type' => 'textfield',
      '#required' => true,
      '#description' => t('The object\'s title'),
      '#default_value' => isset($form_state['values']['title']) ? $form_state['values']['title'] : NULL,
    ),
    'redirect_url' => array(
      '#title' => t('Redirect URL'),
      '#type' => 'textfield',
      '#required' => true,
      '#description' => t('The URL that a user will be redirected to when accessing this object.'),
      '#default_value' => isset($form_state['values']['redirect_url']) ? $form_state['values']['redirect_url'] : NULL,
    ),
  );
}

/**
 * Submit handler, adds uploaded file to ingest object.
 *
 * @param array $form
 *   The Drupal form.
 * @param array $form_state
 *   The Drupal form state.
 */
function uofm_redirect_object_upload_form_submit(array $form, array &$form_state) {
  $object = islandora_ingest_form_get_object($form_state);
  $title = $form_state['values']['title'];
  if (!empty($title)) {
    $object->label = $title;
    $file = _uofm_redirect_object($title);
    if (empty($object['OBJ'])) {
      $ds = $object->constructDatastream('OBJ', 'M');
      $object->ingestDatastream($ds);
    }
    else {
      $ds = $object['OBJ'];
    }
    $ds->setContentFromFile($file);
    $ds->label = $title;
    $ds->mimetype = "image/png";
    file_unmanaged_delete($file);
  }
  $url = $form_state['values']['redirect_url'];
  if (empty($object['RELS-INT'])) {
    $rels = $object->constructDatastream('RELS-INT', 'X');
    $rels->mimetype = 'application/rdf+xml';
    $rels->label = t('Fedora internal relationships');
    $rels->relationships->registerNamespace('uofm-model', UOFM_REDIRECT_PREDICATE_NS);
    $object->ingestDatastream($rels);
  }
  else {
    $rels = $object['RELS-INT'];
  }
  $rels->relationships->remove(UOFM_REDIRECT_PREDICATE_NS, UOFM_REDIRECT_PREDICATE_TAG, NULL);
  $rels->relationships->add(UOFM_REDIRECT_PREDICATE_NS, UOFM_REDIRECT_PREDICATE_TAG, $url, RELS_TYPE_FULL_URI);
}

/**
 * Generate the image with the title as text.
 * @param string $title The title.
 * @return string The path to the image.
 */
function _uofm_redirect_object($title) {
  $convert = variable_get('imagemagick_convert', 'convert');
  $name = tempnam(file_directory_temp(), 'uofm_redirect_object_');
  $tmpName = $name . '-tmp.png';
  file_unmanaged_delete($name);

  $cmd = escapeshellcmd($convert) . " -size 1000x1000 xc:white ";
  $cmd .= " -pointsize 100 -fill black ";
  $cmd .= "-gravity center -annotate +0+0 " . escapeshellarg(wordwrap($title, 15)) ;
  $cmd .= " " . $tmpName;
  exec($cmd);
  $cmd = escapeshellcmd($convert) . " $tmpName -resize 300x300 $name";
  exec($cmd);
  file_unmanaged_delete($tmpName);
  return $name;
}