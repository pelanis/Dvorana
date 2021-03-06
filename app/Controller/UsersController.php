<?php
App::uses('AppController', 'Controller');
/**
 * Users Controller
 *
 * @property User $User
 * @property PaginatorComponent $Paginator
 */
class UsersController extends AppController
{

    public function beforeFilter()
    {
        parent::beforeFilter();

        $this->Auth->allow('add', 'logout');
        if ($this->request->params['action'] == "edit" &&
            $this->request->params['pass'][0] == $this->Auth->user('id')
        ) {
            $this->Auth->allow('edit');
        }

    }

    public function login()
    {
        if ($this->request->is('post')) {
            if ($this->Auth->login()) {
                return $this->redirect($this->Auth->redirect());
            }
            $this->Session->setFlash(__('Korisničko ime ili lozinka nisu tačni, molimo pokušajte ponovo.'));
        }
    }

    public function logout()
    {
        return $this->redirect($this->Auth->logout());
    }

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
        $this->User->recursive = 0;
        $this->set('users', $this->Paginator->paginate());
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
        if (!$this->User->exists($id)) {
            throw new NotFoundException(__('Invalid user'));
        }
        $options = array('conditions' => array('User.' . $this->User->primaryKey => $id));
        $this->set('user', $this->User->find('first', $options));
    }

    /**
     * add method
     *
     * @return void
     */
    public function add()
    {
        if ($this->request->is('post')) {
            if ($this->Auth->user()) {
                $loggedUser = true;
            } else {
                $loggedUser = false;
            }


            if(!$loggedUser){
                $this->request->data["User"]["role"]="Klijent";
            }

            $this->User->create();
            if ($this->User->save($this->request->data)) {
                if (!$loggedUser) {
                    $id = $this->User->id;
                    $this->request->data['User'] = array_merge(
                        $this->request->data['User'],
                        array('id' => $id)
                    );
                    $this->Session->setFlash(__('Uspješna registracija. Dobrodošao/la '.$this->request->data['User']['name'].'!'));
                    $this->Auth->login($this->request->data['User']);
                }
                //$this->Session->setFlash(__('The user has been saved.'));
                return $this->redirect(array('controller' => 'terms', 'action' => 'index'));
            } else {
                $this->Session->setFlash(__('Korisnik nije mogao biti sačuvan. Molimo pokušajte ponovo.'));
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
        if (!$this->User->exists($id)) {
            throw new NotFoundException(__('Invalid user'));
        }
        if ($this->request->is(array('post', 'put'))) {
            if (empty($this->request->data['User']['password'])) {
//                $user = $this->User->findById($id);
//                $this->request->data['User']['password'] =  $user['User']['password'];
                unset($this->request->data['User']['password']);
            }

            if ($this->Auth->user('role') == "Klijent"){
                $this->request->data["User"]["role"]="Klijent";
            }

            if ($this->User->save($this->request->data)) {
                $this->Session->setFlash(__('Korisnik je sačuvan.'));
                if ($this->Auth->user('role') == "Klijent") {
                    return $this->redirect(array('action' => 'edit', $this->Auth->user('id')));
                } else {
                    return $this->redirect(array('action' => 'index'));
                }

            } else {
                $this->Session->setFlash(__('Korisnik nije mogao biti sačuvan. Molimo pokušajte ponovo.'));
            }
        } else {
            $options = array('conditions' => array('User.' . $this->User->primaryKey => $id));
            $this->request->data = $this->User->find('first', $options);
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
        $this->User->id = $id;
        if (!$this->User->exists()) {
            throw new NotFoundException(__('Invalid user'));
        }
        $this->request->onlyAllow('post', 'delete');
        if ($this->User->delete()) {
            $this->Session->setFlash(__('Korisnik je obrisan.'));
        } else {
            $this->Session->setFlash(__('Korisnik nije mogao biti obrisan. Molimo pokušajte ponovo.'));
        }
        return $this->redirect(array('action' => 'index'));
    }
}
