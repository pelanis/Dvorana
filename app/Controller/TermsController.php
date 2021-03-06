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
    public $components = array('Paginator', 'RequestHandler');

    public $helpers = array('Js');

    public $uses = array('Term', 'Setting');

    private $cijena = 5;

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
                $this->Session->setFlash(__('Termin je sačuvan.'));
                return $this->redirect(array('action' => 'index'));
            } else {
                $this->Session->setFlash(__('Termin nije uspješno sačuvan. Molimo pokušajte ponovo.'));
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
                    $this->Session->setFlash(__('Termin je sačuvan.'));
                    return $this->redirect(array('action' => 'index'));
                } else {
                    $this->Session->setFlash(__('Termin nije moguće sačuvati. Molimo pokušajte ponovo.'));
                }
            } else {
                $options = array('conditions' => array('Term.' . $this->Term->primaryKey => $id));
                $this->request->data = $this->Term->find('first', $options);
            }
        } else {
            $this->Session->setFlash(__('Termin nije moguće uređivati.'));
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
        if ($status == "")
            $status = "nepotvrđen";
        $hall = intval($_POST['hall']);

        if (isset($id) && $this->Term->exists($id)) {
            $currentDay = $this->Term->find('all', array(
                'conditions' => array('Term.date' => date("Y-m-d", strtotime($date)),
                    'Term.id <>' => $id,
                    'Term.hall_id' => $hall)
            ));

            $this->checkOverlap($currentDay, $start, $end);

            $price = $this->getPrice($date, $start, $end);

            if ($_POST['iznos'] != "") {
                $price = (float)$_POST['iznos'];
            }

            $this->Term->read(null, $id);
            $this->Term->set(array(
                'start' => $start,
                'end' => $end,
                'date' => date("Y-m-d", strtotime($date)),
                'comment' => $_POST['body'],
                'status' => $status,
                'price' => $price
            ));
            $this->Term->save();
        } else {


            $currentDay = $this->Term->find('all', array(
                'conditions' => array('Term.date' => date("Y-m-d", strtotime($date)),
                    'Term.hall_id' => $hall)
            ));

            $this->checkOverlap($currentDay, $start, $end);

            $price = $this->getPrice($date, $start, $end);

            $this->request->data['Term']['client_id'] = $this->Auth->user('id');
            $this->request->data['Term']['status'] = $status;
            $this->request->data['Term']['start'] = $start;
            $this->request->data['Term']['end'] = $end;
            $this->request->data['Term']['date'] = date("Y-m-d", strtotime($date));
            $this->request->data['Term']['comment'] = $_POST['body'];
            $this->request->data['Term']['hall_id'] = $hall;
            $this->request->data['Term']['price'] = $price;
            if ($this->Term->save($this->request->data)) {
                $term_id = $this->Term->getLastInsertId();
                echo json_encode(intval($term_id));
            }
        }

        exit;

    }

    public function getEvents()
    {
        $dan = $_REQUEST['dan'];
        $lastDay = date('Y-m-d', strtotime($dan . ' + 6 days'));

        $options = array('order' => array('Terms.start' => 'desc'));

        $allTerms = $this->Term->find('all', array(
            'conditions' => array(
                'Term.date >=' => $dan,
                'Term.date <=' => $lastDay
            )
        ));
        $terms = array();
        $ownerTerms = array();


        foreach ($allTerms as $row) {
            if ($row['Term']['hall_id'] == $_REQUEST['hall']) {
                if ($row['Term']['client_id'] == $this->Auth->user('id')) {
                    $ownerTerms[] = $row['Term']['id'];
                }

                $date = $row['Term']['date'];
                $start = $row['Term']['start'];
                $end = $row['Term']['end'];
                $termArray['id'] = $row['Term']['id'];
                $termArray['status'] = $row['Term']['status'];
                $termArray['body'] = $row['Term']['comment'];
                $termArray['start'] = date('Y-m-d\T', strtotime($date)) . date('H:i', strtotime($start));
                $termArray['end'] = date('Y-m-d\T', strtotime($date)) . date('H:i', strtotime($end));
                $termArray['hall'] = $row['Term']['hall_id'];
                $terms[] = $termArray;
            }
        }

        $menadzer = ($this->Auth->user('role') == 'Menadžer');

        $limit = $this->Setting->find('first', array(
            'conditions' => array('Setting.name' => 'limit')
        ));

        $terms[] = array("owner" => $ownerTerms);

        $terms[] = array("menadzer" => $menadzer);

        $terms[] = array("limit" => $limit['Setting']['value']);

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

        $currentDay = $this->Term->find('all', array(
            'conditions' => array('Term.date' => date("Y-m-d", strtotime($date)),
                'Term.id <>' => $id)
        ));
        $this->checkOverlap($currentDay, $start, $end);

        $price = $this->getPrice($date, $start, $end);
        $this->Term->read(null, $id);
        $this->Term->set(array(
            'start' => $start,
            'end' => $end,
            'date' => date("Y-m-d", strtotime($date)),
            'price' => $price
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

//    public function owner()
//    {
//
//        $id = $_POST['id'];
//        $this->Term->id = $id;
//
//        $menadzer = ($this->Auth->user('role') == 'Menadžer');
//        $own = $this->isOwned($id);
//
//        echo json_encode(array("owner" => $own, "menadzer" => $menadzer));
//        exit;
//    }


    public function izvjestaj()
    {


        $this->Term->recursive = 0;

        if ($this->Auth->user('role') == 'Klijent') {
            $this->Paginator->settings = array(
                'conditions' => array('Term.client_id' => $this->Auth->user('id')),
                'limit' => 10,
                'fields' => array('Term.id', "DATE_FORMAT(Term.date, '%d.%m.%Y.') as date", "concat(DATE_FORMAT(Term.start, '%H:%i'),'-',DATE_FORMAT(Term.end, '%H:%i')) as term", 'Term.status', 'Hall.name', 'Term.comment', 'Term.price')
            );
            $data = $this->Paginator->paginate('Term');
            $this->set('terms', $data);
        } else {
            $this->Paginator->settings = array(
                'limit' => 10,
                'fields' => array('Term.id', "DATE_FORMAT(Term.date, '%d.%m.%Y.') as date", "concat(DATE_FORMAT(Term.start, '%H:%i'),'-',DATE_FORMAT(Term.end, '%H:%i')) as term", "User.name, User.surname", 'Term.status', 'Hall.name', 'Term.comment', 'Term.price')
            );
            $data = $this->Paginator->paginate('Term');
            $this->set('terms', $data);
        }
    }

    function KonverzijaDatum($mysqlDatum)
    {

        $tmp = explode(".", $mysqlDatum);
        if (count($tmp) < 3) {
            return "";
        }

        $DATUM = $tmp[2] . "-" . $tmp[1] . "-" . $tmp[0];
        return $DATUM;


    }


    public function search()
    {

        if ($this->request->is('ajax')) {

            if(isset($_REQUEST["TermDate"])){
                $keyword = $this->KonverzijaDatum($_REQUEST["TermDate"]);
            }
            else
            {
                $keyword = "";
            }

            if(isset($_REQUEST["TermOd"])){
                $od = $this->KonverzijaDatum($_REQUEST["TermOd"]);
            }
            else
            {
                $od = "";
            }

            if(isset($_REQUEST["TermDo"])){
                $do = $this->KonverzijaDatum($_REQUEST["TermDo"]);
            }
            else
            {
                $do = "";
            }



            $hall = $_REQUEST["TermHall"];
            $status = "";
            $name = "";
            $surname = "";

            if ($this->Auth->user('role') == 'Menadžer') {
                $status = $_REQUEST["TermStatus"];
                $name = $_REQUEST["TermName"];
                $surname = $_REQUEST["TermSurname"];
            }


            if(isset($_REQUEST["TermVrijemeOdHour"])){
                if ($_REQUEST["TermVrijemeOdHour"] != "") {
                    $vrijemeOd = $_REQUEST["TermVrijemeOdHour"] .
                        ':' .
                        $_REQUEST["TermVrijemeOdMin"] .
                        ':' .
                        '00';
                    $_REQUEST["vrijemeOd"] = $vrijemeOd;
                } else {
                    $vrijemeOd = "";
                    $_REQUEST["vrijemeOd"] = "";
                }
            }
            else
            {
                $vrijemeOd = "";
                $_REQUEST["vrijemeOd"] = "";

            }


            if(isset($_REQUEST["TermVrijemeDoHour"])){
                if ($_REQUEST["TermVrijemeDoHour"] != "") {
                    $vrijemeDo = $_REQUEST["TermVrijemeDoHour"] .
                        ':' .
                        $_REQUEST["TermVrijemeDoMin"] .
                        ':' .
                        '00';
                    $_REQUEST["vrijemeDo"] = $vrijemeDo;
                } else {
                    $vrijemeDo = "";
                    $_REQUEST["vrijemeDo"] = "";
                }
            }
            else
            {
                $vrijemeDo = "";
                $_REQUEST["vrijemeDo"] = "";
            }


            if(isset($_REQUEST["TermVrijemeTHour"])){
                if ($_REQUEST["TermVrijemeTHour"] != "") {
                    $vrijemeT = $_REQUEST["TermVrijemeTHour"] .
                        ':' .
                        $_REQUEST["TermVrijemeTMin"] .
                        ':' .
                        '00';
                    $_REQUEST["vrijemeT"] = $vrijemeT;
                } else {
                    $vrijemeT = "";
                    $_REQUEST["vrijemeT"] = "";
                }
            }
            else
            {
                $vrijemeT = "";
                $_REQUEST["vrijemeT"] = "";
            }



            $this->Term->recursive = 0;

            $range = array();
            $num = count(array_filter($_REQUEST));
            if (count(array_filter($_REQUEST)) > 1) {
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
                if ($vrijemeT != "") {
                    $cond[] = array('Term.start' => $vrijemeT);
                }
                if ($vrijemeOd != "") {
                    $cond[] = array('Term.start >=' => $vrijemeOd);
                }
                if ($vrijemeDo != "") {
                    $cond[] = array('Term.start <=' => $vrijemeDo);
                }

                if ($name != "") {
                    $cond[] = array('User.name' => $name);
                }
                if ($surname != "") {
                    $cond[] = array('User.surname' => $surname);
                }
                $range = array('AND' => $cond);
            } else {
                $range = array('OR' => array(
                    'Term.date' => $keyword,
                    'Term.date >=' => $od,
                    'Term.date <=' => $do,
                    'Term.hall_id' => $hall,
                    'Term.status' => $status,
                    'Term.start >=' => $vrijemeOd,
                    'Term.start <=' => $vrijemeDo,
                    'Term.start' => $vrijemeT,
                    'User.name' => $name,
                    'User.surname' => $surname));
            }


            if ($this->Auth->user('role') == 'Klijent') {
                $this->Paginator->settings = array(
                    'conditions' => array('Term.client_id' => $this->Auth->user('id'),
                        $range
                    ),
                    'limit' => 10,
                    'fields' => array('Term.id', "DATE_FORMAT(Term.date, '%d.%m.%Y.') as date", "concat(DATE_FORMAT(Term.start, '%H:%i'),'-',DATE_FORMAT(Term.end, '%H:%i')) as term", 'Term.status', 'Hall.name', 'Term.comment', 'Term.price')
                );
                $data = $this->Paginator->paginate('Term');
                $this->set('terms', $data);
            } else {
                $this->Paginator->settings = array(
                    'conditions' => array($range),
                    'limit' => 10,
                    'fields' => array('Term.id', "DATE_FORMAT(Term.date, '%d.%m.%Y.') as date", "concat(DATE_FORMAT(Term.start, '%H:%i'),'-',DATE_FORMAT(Term.end, '%H:%i')) as term", "User.name, User.surname", 'Term.status', 'Hall.name', 'Term.comment', 'Term.price')
                );
                $data = $this->Paginator->paginate('Term');
                $this->set('terms', $data);
            }

        } else {
            $keyword = $this->KonverzijaDatum($this->request->data['Term']['date']);
            $od = $this->KonverzijaDatum($this->request->data['Term']['od']);
            $do = $this->KonverzijaDatum($this->request->data['Term']['do']);
            $hall = $this->request->data['Term']['hall'];
            $status = "";
            $name = "";
            $surname = "";

            if ($this->Auth->user('role') == 'Menadžer') {
                $status = $this->request->data['Term']['status'];
                $name = $this->request->data['Term']['name'];
                $surname = $this->request->data['Term']['surname'];
            }


            if ($this->request->data['Term']['vrijemeOd']['hour'] != "") {
                $vrijemeOd = $this->request->data['Term']['vrijemeOd']['hour'] .
                    ':' .
                    $this->request->data['Term']['vrijemeOd']['min'] .
                    ':' .
                    '00';
                $this->request->data['Term']['vrijemeOd'] = $vrijemeOd;
            } else {
                $vrijemeOd = "";
                $this->request->data['Term']['vrijemeOd'] = "";
            }

            if ($this->request->data['Term']['vrijemeDo']['hour'] != "") {
                $vrijemeDo = $this->request->data['Term']['vrijemeDo']['hour'] .
                    ':' .
                    $this->request->data['Term']['vrijemeDo']['min'] .
                    ':' .
                    '00';
                $this->request->data['Term']['vrijemeDo'] = $vrijemeDo;
            } else {
                $vrijemeDo = "";
                $this->request->data['Term']['vrijemeDo'] = "";
            }


            if ($this->request->data['Term']['vrijemeT']['hour'] != "") {
                $vrijemeT = $this->request->data['Term']['vrijemeT']['hour'] .
                    ':' .
                    $this->request->data['Term']['vrijemeT']['min'] .
                    ':' .
                    '00';
                $this->request->data['Term']['vrijemeT'] = $vrijemeT;
            } else {
                $vrijemeT = "";
                $this->request->data['Term']['vrijemeT'] = "";
            }


            $this->Term->recursive = 0;

            $range = array();
            $num = count(array_filter($this->request->data['Term']));
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
                if ($vrijemeT != "") {
                    $cond[] = array('Term.start' => $vrijemeT);
                }
                if ($vrijemeOd != "") {
                    $cond[] = array('Term.start >=' => $vrijemeOd);
                }
                if ($vrijemeDo != "") {
                    $cond[] = array('Term.start <=' => $vrijemeDo);
                }

                if ($name != "") {
                    $cond[] = array('User.name' => $name);
                }
                if ($surname != "") {
                    $cond[] = array('User.surname' => $surname);
                }
                $range = array('AND' => $cond);
            } else {
                $range = array('OR' => array('Term.date' => $keyword,
                    'Term.date >=' => $od,
                    'Term.date <=' => $do,
                    'Term.hall_id' => $hall,
                    'Term.status' => $status,
                    'Term.start >=' => $vrijemeOd,
                    'Term.start <=' => $vrijemeDo,
                    'Term.start' => $vrijemeT,
                    'User.name' => $name,
                    'User.surname' => $surname));
            }

            if ($this->Auth->user('role') == 'Klijent') {
                $this->Paginator->settings = array(
                    'conditions' => array('Term.client_id' => $this->Auth->user('id'),
                        $range
                    ),
                    'limit' => 10,
                    'fields' => array('Term.id', "DATE_FORMAT(Term.date, '%d.%m.%Y.') as date", "concat(DATE_FORMAT(Term.start, '%H:%i'),'-',DATE_FORMAT(Term.end, '%H:%i')) as term", 'Term.status', 'Hall.name', 'Term.comment', 'Term.price')
                );
                $data = $this->Paginator->paginate('Term');
                $this->set('terms', $data);
            } else {
                $this->Paginator->settings = array(
                    'conditions' => array($range),
                    'limit' => 10,
                    'fields' => array('Term.id', "DATE_FORMAT(Term.date, '%d.%m.%Y.') as date", "concat(DATE_FORMAT(Term.start, '%H:%i'),'-',DATE_FORMAT(Term.end, '%H:%i')) as term", "User.name, User.surname", 'Term.status', 'Hall.name', 'Term.comment', 'Term.price')
                );
                $data = $this->Paginator->paginate('Term');
                $this->set('terms', $data);
            }
        }

    }

    public function getPrice($date, $start, $end)
    {
        $startTime = new DateTime(date("Y-m-d", strtotime($date)) . 'T' . $start);
        $endTime = new DateTime(date("Y-m-d", strtotime($date)) . 'T' . $end);
        $interval = $startTime->diff($endTime);
        $minutes = $interval->i + $interval->h * 60;

        return $minutes * $this->cijena / 15;

    }

    /**
     * @param $currentDay
     * @param $start
     * @param $end
     */
    public function checkOverlap($currentDay, $start, $end)
    {
        $overlap = false;

        foreach ($currentDay as $row) {
            if (($row['Term']['start'] >= $start && $row['Term']['start'] < $end) ||
                $row['Term']['end'] > $start && $row['Term']['end'] <= $end
            ) {
                $overlap = true;
            }
        }

        if ($overlap) {
            echo json_encode("error");
            exit;
        }
    }
}
