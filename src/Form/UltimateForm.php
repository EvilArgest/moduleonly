<?php

namespace Drupal\ultimate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides table for.
 */
class UltimateForm extends FormBase {

  /**
   * Titles of the header.
   */
  protected $titles;

  /**
   * Titles of the header.
   */
  protected $intitles;

  /**
   * Amount of tables to be built.
   */
  protected $tables = 1;

  /**
   * Amount of rows to be built for each table.
   */
  protected $rows = 1;

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'ultimate_table';
  }

  /**
   * Building header.
   */
   protected function buildTitles(): void {
     $this->titles = [
       'year' => $this->t('Year'),
       'jan' => $this->t('Jan'),
       'feb' => $this->t('Feb'),
       'mar' => $this->t('Mar'),
       'q1' => $this->t('Q1'),
       'apr' => $this->t('Apr'),
       'may' => $this->t('May'),
       'jun' => $this->t('Jun'),
       'q2' => $this->t('Q2'),
       'jul' => $this->t('Jul'),
       'aug' => $this->t('Aug'),
       'sep' => $this->t('Sep'),
       'q3' => $this->t('Q3'),
       'oct' => $this->t('Oct'),
       'nov' => $this->t('Nov'),
       'dec' => $this->t('Dec'),
       'q4' => $this->t('Q4'),
       'ytd' => $this->t('YTD'),
     ];
   }

  /**
   * Returning values of inactive cells.
   */
   protected function inactiveCells(): void {
     $this->intitles = [
       'q1' => $this->t('Q1'),
       'q2' => $this->t('Q2'),
       'q3' => $this->t('Q3'),
       'q4' => $this->t('Q4'),
       'year' => $this->t('Year'),
       'ytd' => $this->t('YTD'),
     ];
   }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id = "form_wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached'] = ['library' => ['ultimate/ultimate_library']];

    // Calling the functions for building the table.
    $this->inactiveCells();
    $this->buildTitles();
    $this->buildTable($form, $form_state);

    // Adding buttons.
    $form['addTable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Table'),
      '#submit' => ['::addTable'],
      // We can add rows and tables even if we have errors.
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::submitAjaxForm',
        'wrapper' => 'form_wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $form['addRow'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Row'),
      '#submit' => ['::addRow'],
      // We can add rows and tables even if we have errors.
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::submitAjaxForm',
        'wrapper' => 'form_wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::submitAjaxForm',
        'wrapper' => 'form_wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    return $form;
  }

  /**
   * Function adds a new table.
   */
  protected function buildTable(array &$form, FormStateInterface $form_state): void {
    for ($i = 0; $i < $this->tables; $i++) {
      $table_key = $i;
      $form[$table_key] = [
        '#type' => 'table',
        '#header' => $this->titles,
        '#tree' => TRUE,
      ];
      $this->buildRow($table_key, $form[$table_key], $form_state);
    }
  }

  /**
   * Function adds rows to the existing table.
   */
  protected function buildRow($table_key, array &$tablecell, FormStateInterface $form_state): void {
    for ($i = $this->rows; $i > 0; $i--) {
      foreach ($this->titles as $key => $value) {
        $tablecell[$i][$key] = [
          '#type' => 'number',
          '#step' => '0.01',
        ];
        if (array_key_exists($key, $this->intitles)) {
          //Setting the default value to inactive cells.
          $value = $form_state->getValue($table_key . '][' . $i . '][' . $key, 0);
          $tablecell[$i][$key]['#default_value'] = round($value, 2);
          $tablecell[$i][$key]['#disabled'] = TRUE;
        }
      }
      $tablecell[$i]['year']['#default_value'] = date('Y') - $i + 1;
    }
  }

  /**
   * Function which adds a new row to the table by incrementing rows.
   */
  public function addRow(array $form, FormStateInterface $form_state): array {
    $this->rows++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Function which adds a new table by incrementing tables.
   */
  public function addTable(array $form, FormStateInterface $form_state): array {
    $this->tables++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Transforming array.
   */
  public function arrayTransform($array): array {
    $values = [];
    $inactive_cells = $this->intitles;
    for ($i = $this->rows; $i > 0; $i--) {
      // Setting array from active cells only.
      foreach ($array[$i] as $key => $value) {
        if (!array_key_exists($key, $inactive_cells)) {
          $values[] = $value;
        }
      }
    }
    return $values;
  }

  /**
   * Refreshing the page.
   */
  public function submitAjaxForm(array $form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * Validating the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Getting the values.
    $table_values = $form_state->getValues();
    // The start point.
    $start_point = NULL;
    // The end point.
    $end_point = NULL;
    // Here we save the values from the table.
    $active_values = [];
    for ($i = 0; $i < $this->tables; $i++) {
      // Getting the values from the table.
      $values = $this->arrayTransform($table_values[$i]);
      // Saving the values here.
      $active_values[] = $values;
      // Going through every cell in the table.
      foreach ($values as $key => $value) {
        // Comparing every cell in the tables.
        for ($j = 0; $j < 12; $j++) {
          if (empty($active_values[0][$j]) !== empty($active_values[$i][$j])) {
            $form_state->setErrorByName($i, 'Tables are not the same.');
          }
        }
        // Getting the cell from where we start.
        if (!empty($value)) {
          $start_point = $key;
          break;
        }
      }
      // If the start cell exist.
      if ($start_point !== NULL) {
        for ($l = $start_point; $l < count($values) + 1; $l++) {
          if (empty($values[$l])) {
            $end_point = $l;
            break;
          }
        }
      }
      // Going to the last filled +1 cell.
      if ($end_point !== NULL) {
        for ($f = $end_point; $f < count($values) + 1; $f++) {
          if (!empty($values[$f])) {
            $form_state->setErrorByName($f, 'Form is not valid.');
          }
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table_values = $form_state->getValues();
      // Going through values of the table.
      foreach ($table_values as $table_key => $tablecell) {
        foreach ($tablecell as $row_key => $rows) {
          // Getting the cell we need.
          $my_cell = $table_key . '][' . $row_key . '][';
          // Setting up the q1-2-3-4 and ytd value.
          if (($rows['jan'] + $rows['feb'] + $rows['mar']) === 0) {
            $q1 = 0;
          }
          else {
            $q1 = ($rows['jan'] + $rows['feb'] + $rows['mar'] + 1) / 3;
          }
          if (($rows['apr'] + $rows['may'] + $rows['jun']) === 0) {
            $q2 = 0;
          }
          else {
            $q2 = ($rows['apr'] + $rows['may'] + $rows['jun'] + 1) / 3;
          }
          if (($rows['jul'] + $rows['aug'] + $rows['sep']) === 0) {
            $q3 = 0;
          }
          else {
            $q3 = ($rows['jul'] + $rows['aug'] + $rows['sep'] + 1) / 3;
          }
          if (($rows['oct'] + $rows['nov'] + $rows['dec']) === 0) {
            $q4 = 0;
          }
          else {
            $q4 = ($rows['oct'] + $rows['nov'] + $rows['dec'] + 1) / 3;
          }
          if ((($q1 + $q2 + $q3 + $q4 + 1) / 4) === 0) {
            $ytd = 0;
          }
          else {
            $ytd = ($q1 + $q2 + $q3 + $q4 + 1) / 4;
          }
          // Setting the value of the field.
          $form_state->setValue($my_cell . 'q1', $q1);
          $form_state->setValue($my_cell . 'q2', $q2);
          $form_state->setValue($my_cell . 'q3', $q3);
          $form_state->setValue($my_cell . 'q4', $q4);
          $form_state->setValue($my_cell . 'ytd', $ytd);
      }
    }
    $this->messenger()->addStatus("Hurray! The form is valid");
    $form_state->setRebuild();
  }

}
