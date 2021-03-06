<?php

final class PhabricatorProjectBoardEditController
  extends PhabricatorProjectController {

  private $id;
  private $projectID;

  public function willProcessRequest(array $data) {
    $this->projectID = $data['projectID'];
    $this->id = idx($data, 'id');
  }

  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $project = id(new PhabricatorProjectQuery())
      ->setViewer($viewer)
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->withIDs(array($this->projectID))
      ->executeOne();

    if (!$project) {
      return new Aphront404Response();
    }

    $is_new = ($this->id ? false : true);

    if (!$is_new) {
      // TODO: LATER!
      throw new Exception("When I'm ready!");
    } else {
      $column = new PhabricatorProjectColumn();
    }

    $errors = array();
    $e_name = true;
    $error_view = null;
    $view_uri = $this->getApplicationURI('/board/'.$this->projectID.'/');

    if ($request->isFormPost()) {
      $new_name = $request->getStr('name');
      $column->setName($new_name);

      if (!strlen($column->getName())) {
        $errors[] = pht('Column name is required.');
        $e_name = pht('Required');
      } else {
        $e_name = null;
      }

      $column->setProjectPHID($project->getPHID());
      $column->attachProject($project);
      $column->setSequence(0);

      if (!$errors) {
        $column->save();
        return id(new AphrontRedirectResponse())->setURI($view_uri);
      }
    }

    $form = new AphrontFormView();
    $form->setUser($request->getUser())
      ->appendChild(
        id(new AphrontFormTextControl())
          ->setValue($column->getName())
          ->setLabel(pht('Name'))
          ->setName('name')
          ->setError($e_name)
          ->setCaption(
            pht('This will be displayed as the header of the column.')));

    if ($is_new) {
      $title = pht('Create Column');
      $submit = pht('Create Column');
    } else {
      $title = pht('Edit %s', $column->getName());
      $submit = pht('Save Column');
    }

    $form->appendChild(
      id(new AphrontFormSubmitControl())
        ->setValue($submit)
        ->addCancelButton($view_uri));

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb($title);

    $form_box = id(new PHUIObjectBoxView())
      ->setHeaderText($title)
      ->setFormErrors($errors)
      ->setForm($form);

    return $this->buildApplicationPage(
      array(
        $crumbs,
        $form_box,
      ),
      array(
        'title' => $title,
        'device' => true,
      ));
  }
}
