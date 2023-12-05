<?php

namespace Drupal\sms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\sms\Service\SmsService;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Class AddFeeForm Form.
 * @package Drupal\sms\Form
 */
class AddFeeForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Custom sms service.
   *
   * @var \Drupal\sms\Service\SmsService
   */
  protected $smsService;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SmsService $smsService) {
    $this->entityTypeManager = $entity_type_manager->getStorage('node');
    $this->smsService = $smsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity_type.manager'),
      $container->get('sms.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_fee_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

  //   $query = \Drupal::entityQuery('node')
  //   ->condition('type', 'student_information')
  //  // ->condition('title', $input, 'CONTAINS')
  //   ->condition('field_student_name','radhe','CONTAINS')
  //   ->groupBy('nid')
  // //  ->sort('title', 'ASC')
  //   ->accessCheck(TRUE);
  // $ids = $query->execute();

  // $nodes = $ids ? \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids) : [];
  // dump($nodes);

  


    $form['fee'] = [
      '#type' => 'fieldset',
      '#title' => t('School Information'),
      '#collapsible' => TRUE,
    ];
    $form['fee']['registration_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration ID'),
      '#autocomplete_route_name' => 'sms.student_list_autocomplete',
      '#default_value' => '',
      '#required' => TRUE,
      '#field_suffix' => '<span id="student_info"></span><p id="fee_reports"></p>',
      '#ajax' => [
        'callback' => '::getStudentInfo',
        'event' => 'change',
        'wrapper' => 'student_info',
      ],
    ];
    $form['fee']['class'] = [
      '#type' => 'select',
      '#title' => t('Class'),
      '#options' => $this->smsService->getAllVocabularyTerms('class'),
      '#empty_option'=> '--Select Class--',
      '#required' => TRUE
    ];
    $form['fee']['batch_session'] = [
      '#type' => 'select',
      '#title' => $this->t('Batch Session'),
      '#options' => $this->smsService->getAllVocabularyTerms('batch_session'),
      '#empty_option'=> '--Select Batch--',
      '#required' => TRUE
    ];

    $form['fee']['deatils'] = [
      '#type' => 'fieldset',
      '#title' => t('Fee Information'),
      '#collapsible' => TRUE,
    ];
    $form['fee']['deatils']['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Date'),
      //'#date_date_format' => 'd-m-Y',
     // '#dateformat' => 'd-m-Y',
      '#default_value' => date('Y-m-d', \Drupal::time()->getRequestTime()),
    ];
    $form['fee']['deatils']['deposit_fee'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deposit Fee'),
      '#default_value' => '',
      '#required' => TRUE
    ];
    $form['fee']['deatils']['comment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Comment'),
      '#default_value' => '',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save Fee'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('registration_id'))) {
      $student_id = $form_state->getValue('registration_id');
      preg_match('#\((.*?)\)#', $student_id, $match);
      $student_id = $match[1];
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($student_id);
      $RegID = $node->title->value;
      $Class = $node->field_class->target_id;
      $Session = $node->field_batch_session->target_id;
 
      $fee_type_title = 'RegID:'.$RegID.'&Class:'.$Class.'&Session:'.$Session.'&Sid:'.$student_id;
      $fee_report_get = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'fee_mangement', 'title' => $fee_type_title]);
      $fee_type = reset($fee_report_get);

      $values = $form_state->getValues();
      $deposit_fee = $values['deposit_fee'];
      $due_fee = $fee_type->field_fee_due->value;
      
      if ((int)$deposit_fee > (int) $due_fee) {
        $form_state->setErrorByName('deposit_fee','Deposit fee is higher than the due fee.');
      }

      // dump($deposit_fee);
      // dump($due_fee);
      // dump($fee_type); die;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    //$RegID = $values['registration_id'];
    $Class = $values['class'];
    $Session = $values['batch_session'];
    preg_match('#\((.*?)\)#', $values['registration_id'], $match);
    $Sid = $match[1];
    
    $title_exits = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'student_information','nid' => $Sid]);
    $RegID = reset($title_exits)->getTitle();
    
    $fee_title = 'RegID:'.$RegID.'&Class:'.$Class.'&Session:'.$Session.'&Sid:'.$Sid;




    $fee_title_exits = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'fee_mangement', 'title' => $fee_title]);
    if(!empty($fee_title_exits)) {
     // dump($fee_title_exits);
     
      $paragraph = Paragraph::create([
        'type' => 'fee_details',
        'field_date' => $values['date'],
        'field_amount' => $values['deposit_fee'],
        'field_slip_no_comment' => $values['comment'],
      ]);
      $paragraph->save();
      $node_fee_type = reset($fee_title_exits);
      
      $fee_deposit = (int)$node_fee_type->field_fee_deposit->value + (int)$values['deposit_fee'];
      $fee_due = (int)$node_fee_type->field_fee_due->value - (int)$values['deposit_fee'];

      $node_fee_type->field_fee_details->appendItem([
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ]);
      $node_fee_type->field_fee_deposit->value = $fee_deposit;
      $node_fee_type->field_fee_due->value = $fee_due;
      $node_fee_type->save();

      // $paragraph = Paragraph::create([
      //   'type' => 'fee_details',
      //   'field_date' => $values['date'],
      //   'field_amount' => $values['deposit_fee'],
      //   'field_slip_no_comment' => $values['comment'],
      // ]);
      // $paragraph->save();
      // $node_fee_type = reset($fee_title_exits);
      // $node_fee_type->field_fee_details[] = [
      //   'target_id' => $paragraph->id(),
      //   'target_revision_id' => $paragraph->getRevisionId(),
      // ];
      // $node_fee_type->save();
      
    }
    else {
      dump('new page');
      die;
    }


   // die;
  }

  /**
   * Handler for autocomplete request.
   */
  public static function getStudentList_Autocomplete_Json(Request $request) {
    $results = [];
    $input = $request->query->get('q');
    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($results);
    }
    $input = Xss::filter($input);
    
    // $query = \Drupal::entityQuery('node')
    // ->condition('type', 'student_information')
    // ->condition('title', $input, 'CONTAINS')
    // ->groupBy('nid')
    // //->sort('title', 'ASC')
    // ->range(0, 10);
    // $ids = $query->execute();

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'student_information')
      ->condition('field_student_name', $input, 'CONTAINS')
      ->groupBy('nid')
    //  ->sort('title', 'ASC')
      ->accessCheck(TRUE);
    $ids = $query->execute();

    $nodes = $ids ? \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids) : [];
    //$nodes = $ids ? \Drupal::entityTypeManager()->nodeStorage->loadMultiple($ids) : [];
    // dump($nodes);
    //   die;
    foreach ($nodes as $node) {
      switch ($node->isPublished()) {
        case TRUE:
          $availability = '✅';
          break;

        case FALSE:
        default:
          $availability = '🚫';
          break;
      }

      $label = [
        // $node->getTitle(),
        // '<small>(' . $node->id() . $node->field_student_name->value . ')</small>',
        // $availability,
        '<table border="2">
          <tr><td>RegID</td><td>'.$node->id().'</td></tr>
          <tr><td>Student Name</td><td>'.$node->field_student_name->value.'</td></tr>
          <tr><td>Class</td><td>'.$node->field_class->referencedEntities()[0]->getName().'</td></tr>
          <tr><td>Status</td><td>'.$availability.'</td></tr>
        </table>'
      ];

      $results[] = [
        'value' => EntityAutocomplete::getEntityLabels([$node]),
        'label' => implode(' ', $label),
      ];
    }

    return new JsonResponse($results);
  }

  /**
  * Callback get student info change on student reg id.
  */
  public static function getStudentInfo(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!empty($form_state->getValue('registration_id'))) {
      $student_id = $form_state->getValue('registration_id');
      preg_match('#\((.*?)\)#', $student_id, $match);
      $student_id = $match[1];
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($student_id);

     // dump($form_state->getValues()); 
      $RegID = $node->title->value;
      $Class = $node->field_class->target_id;
      $Session = $node->field_batch_session->target_id;
 
      $fee_type_title = 'RegID:'.$RegID.'&Class:'.$Class.'&Session:'.$Session.'&Sid:'.$student_id;
      $fee_report_get = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'fee_mangement', 'title' => $fee_type_title]);
      $fee_type = reset($fee_report_get);
      $fee_balance = '<table border="2">
        <tr><td>Class Fee</td><td>'.$fee_type->field_class_fee->value.'</td></tr>
        <tr><td>Fee Commitment</td><td>'.$fee_type->field_fee_commitment->value.'</td></tr>
        <tr><td>Fee Deposit</td><td>'.$fee_type->field_fee_deposit->value.'</td></tr>
        <tr><td>Fee Due</td><td>'.$fee_type->field_fee_due->value.'</td></tr>
      </table>';
      $response->addCommand(new InvokeCommand('#fee_reports', 'html', [$fee_balance]));

      $classFee = $node->field_class->target_id;
      $batch_session = $node->field_batch_session->target_id;
      $url = $node->toUrl()->toString();
      $link = '<a href="'.$url.'" class="use-ajax button button--small" data-dialog-type="modal">View Student Info</a>';
      $response->addCommand(new InvokeCommand('#student_info', 'html', [$link]));
      $response->addCommand(new InvokeCommand('#edit-class', 'val', [$classFee]));
      $response->addCommand(new InvokeCommand('#edit-batch-session', 'val', [$batch_session]));
    }
    else {
      $response->addCommand(new InvokeCommand('#student_info', 'html', ['']));
    }
    return $response;
  }

}