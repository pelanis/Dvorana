<?php
App::uses('AppController', 'Controller');
/**
 * Terms Controller
 *
 * @property Term $Term
 * @property PaginatorComponent $Paginator
 */
class TermsController extends AppController
{

    /**
     * Components
     *
     * @var array
     */
    public $components = array('Paginator');

    /**
     * index method
     *
     * @return void
     */
    public function index()
    {
        $this->Term->recursive = 0;
        $this->set('terms', $this->Paginator->paginate());
    }

    /**
     * view method
     *
     * @throws NotFoundException
     * @param string $id
     * @return void
     */
    public function view($id = null)
    {
        if (!$this->Term->exists($id)) {
            throw new NotFoundException(__('Invalid term'));
        }
        $options = array('conditions' => array('Term.' . $this->Term->primaryKey => $id));
        $this->set('term', $this->Term->find('first', $options));
    }

    /**
     * add method
     *
     * @return void
     */
    public function add()
    {
        if ($this->request->is('post')) {
            $this->request->data['Term']['client_id'] = $this->Auth->user('id');
            $this->request->data['Term']['status'] = "nepotvrđen";
            if ($this->Term->save($this->request->data)) {
                $this->Session->setFlash(__('The term has been saved.'));
                return $this->redirect(array('action' => 'index'));
            } else {
                $this->Session->setFlash(__('The term could not be saved. Please, try again.'));
            }
        }
    }

    /**
     * edit method
     *
     * @throws NotFoundException
     * @param string $id
     * @return void
     */
    public function edit($id = null)
    {
        if (!$this->Term->exists($id)) {
            throw new NotFoundException(__('Invalid term'));
        }

        if ($this->isOwned($id) or $this->Auth->user('role') == 'Menadžer') {

            if ($this->request->is(array('post', 'put'))) {
                if ($this->Term->save($this->request->data)) {
                    $this->Session->setFlash(__('The term has been saved.'));
                    return $this->redirect(array('action' => 'index'));
                } else {
                    $this->Session->setFlash(__('The term could not be saved. Please, try again.'));
                }
            } else {
                $options = array('conditions' => array('Term.' . $this->Term->primaryKey => $id));
                $this->request->data = $this->Term->find('first', $options);
            }
        } else {
            $this->Session->setFlash(__('The term could not be edited.'));
            return $this->redirect(array('action' => 'index'));
        }
    }

    /**
     * delete method
     *
     * @throws NotFoundException
     * @param string $id
     * @return void
     */
    public function delete($id = null)
    {

        $id = $_POST['id'];
        $this->Term->id = $id;
        if (!$this->Term->exists()) {
            throw new NotFoundException(__('Invalid term'));
        }

        if ($this->isOwned($id) or $this->Auth->user('role') == 'Menadžer') {
            $this->Term->delete();
        }
        exit;
    }

    public function isAuthorized($user)
    {
        // All registered users can add posts
        if (in_array($this->action, array(
            'add', 'index', 'delete', 'view', 'edit', 'getEvents',
            'save', 'otkazi', 'owner', 'move', 'izvjestaj', 'search'))
        ) {
            return true;
        }

        // The owner of a term can edit and delete it
        return parent::isAuthorized($user);
    }

    public function isOwned($termId)
    {
        if ($this->Term->isOwnedBy($termId, $this->Auth->user('id'))) {
            return true;
        } else
            return false;

    }

    public function save()
    {
        date_default_timezone_set('Europe/Sarajevo');

        if (isset($_POST['id'])) {
            $id = $_POST['id'];
        }

        $start = date('H:i:s', (int)$_POST['start']);
        $end = date('H:i:s', (int)$_POST['end']);
        $date = date('c', (int)$_POST['start']);
        $status = $_POST['status'];

        if (isset($id) && $this->Term->exists($id)) {
            $this->Term->read(null, $id);
            $this->Term->set(array(
                'start' => $start,
                'end' => $end,
                'date' =>  date("Y-m-d", strtotime($date)),
                'comment' => $_POST['body'],
                'status' => $status
            ));
            $this->Term->save();
        } else {
            $this->request->data['Term']['client_id'] = $this->Auth->user('id');
            $this->request->data['Term']['status'] = $status;
            $this->request->data['Term']['start'] = $start;
            $this->request->data['Term']['end'] = $end;
            $this->request->data['Term']['date'] = date("Y-m-d", strtotime($date));
            $this->request->data['Term']['comment'] = $_POST['body'];
//            $this->request->data['Term']['term'] = date("G:i-", strtotime($start)) . date("G:i", strtotime($end));
            $this->request->data['Term']['hall_id'] = intval($_POST['hall']);
            if ($this->Term->save($this->request->data)) {
                $term_id = $this->Term->getLastInsertId();
                echo json_encode(intval($term_id));
            }


        }

        exit;

    }

    public function getEvents()
    {
        $options = array('order' => array('Terms.start' => 'desc'));
        $allTerms = $this->Term->find('all');
        $terms = array();
        foreach ($allTerms as $row) {
            if ($row['Term']['hall_id'] == $_REQUEST['hall']) {
                $date = $row['Term']['date'];
                $start = $row['Term']['start'];
                $end =  $row['Term']['end'];
                $termArray['id'] = $row['Term']['id'];
                $termArray['status'] = $row['Term']['status'];
                $termArray['body'] = $row['Term']['comment'];
//                $termArray['start'] = date('Y-m-d\TH:i', strtotime($row['Term']['start']));
                $termArray['start'] = date('Y-m-d\T', strtotime($date)). date('H:i', strtotime($start));
//                $termArray['end'] = date('Y-m-d\TH:i', strtotime($row['Term']['end']));
                $termArray['end'] =  date('Y-m-d\T', strtotime($date)). date('H:i', strtotime($end));
                $termArray['hall'] = $row['Term']['hall_id'];
                $terms[] = $termArray;
            }
        }
        echo json_encode($terms);
        exit;
    }

    public function move()
    {
        date_default_timezone_set('Europe/Sarajevo');
        $id = (int)$_POST['id'];
        if (!$this->Term->exists($id)) {
            exit;
        }

        $start = date('H:i:s', (int)$_POST['start']);
        $end = date('H:i:s', (int)$_POST['end']);
        $date = date('c', (int)$_POST['start']);

        $this->Term->read(null, $id);
        $this->Term->set(array(
            'start' => $start,
            'end' => $end,
            'date' => date("Y-m-d", strtotime($date))
        ));
        $this->Term->save();

        exit;
    }

    public function otkazi()
    {
        $id = (int)$_POST['id'];

        if (!$this->Term->exists($id)) {
            exit;
        }

        $this->Term->read(null, $id);
        $this->Term->set(array(
            'status' => 'otkazan'
        ));
        $this->Term->save();

        exit;
    }

    public function owner()
    {

        $id = $_POST['id'];
        $this->Term->id = $id;

        $menadzer = ($this->Auth->user('role') == 'Menadžer');
        $own = $this->isOwned($id);

        echo json_encode(array("owner" => $own, "menadzer" => $menadzer));
        exit;
    }

    public function izvjestaj()
    {
        $this->Term->recursive = 0;

        if ($this->Auth->user('role') == 'Klijent') {
            $this->Paginator->settings = array(
                'conditions' => array('Term.client_id' => $this->Auth->user('id')),
                'limit' => 10,
                'fields' => array('Term.id', 'Term.date', "concat(DATE_FORMAT(Term.start, '%H:%i'),'-',DATE_FORMAT(Term.end, '%H:%i')) as term" , 'Term.status', 'Term.hall_id', 'Term.comment', 'Term.price')
            );
            $data = $this->Paginator->paginate('Term');
            $this->set('terms', $data);
        } else {
            $this->set('terms', $this->Paginator->paginate());
        }
    }

    public function search()
    {
        if ($this->request->is(array('post', 'put'))) {
            $keyword = $this->request->data['Term']['date'];
            $od = $this->request->data['Term']['od'];
            $do = $this->request->data['Term']['do'];
            $hall = $this->request->data['Term']['hall'];
            $status = $this->request->data['Term']['status'];
            $time = $this->request->data['Term']['vrijemeOd']['hour'].':'.$this->request->data['Term']['vrijemeOd']['min'].':'.'00';
            $this->Term->recursive = 0;

            $range = array();
            if (count(array_filter($this->request->data['Term'])) > 1) {
                $cond = array();
                if ($keyword != "") {
                    $cond[] = array('Term.date' => $keyword);
                }
                if ($od != "") {
                    $cond[] = array('Term.date >=' => $od);
                }
                if ($do != "") {
                    $cond[] = array('Term.date <=' => $do);
                }
                if ($hall != "") {
                    $cond[] = array('Term.hall_id' => $hall);
                }
                if ($status != "") {
                    $cond[] = array('Term.status' => $status);
                }
                $range = array('AND' => $cond);
            } else {
                $range = array('OR' => array('Term.date' => $keyword,
                    'Term.date >=' => $od,
                    'Term.date <=' => $do,
                    'Term.hall_id' => $hall,
                    'Term.status' => $status,
                    'Term.start >=' => $time));
            }

            if ($this->Auth->user('role') == 'Klijent') {
                $this->Paginator->settings = array(
                    'conditions' => array('Term.client_id' => $this->Auth->user('id'),
                        $range
                    ),
                    'limit' => 10,
                    'fields' => array('Term.id', 'Term.date', "concat(DATE_FORMAT(Term.start, '%H:%i'),'-',DATE_FORMAT(Term.end, '%H:%i')) as term", 'Term.status', 'Term.hall_id', 'Term.comment', 'Term.price')
                );
                $data = $this->Paginator->paginate('Term');
                $this->set('terms', $data);
            } else {
                $this->set('terms', $this->Paginator->paginate());
            }


        }
    }
}
