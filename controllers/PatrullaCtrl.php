<?php use Augusthur\Validation as Validate;

class PatrullaCtrl extends RMRController {

    protected $mediaTypes = array('json', 'view');
    protected $properties = array('id', 'nombre', 'descripcion');

    public function queryModel($meth, $repr) {
        return Patrulla::query();
    }

    public function executeListCtrl($paginator) {
        $patrullas = $paginator->rows;
        $nav = $paginator->links;
        $this->render('admin/patrullas.twig', array('patrullas' => $patrullas->toArray(),
                                                    'nav' => $nav));
    }

    public function executeGetCtrl($patrulla) {
        $this->notFound();
    }

    public function modificar($idPat) {
        $vdt = new Validate\Validator();
        $vdt->addRule('idPat', new Validate\Rule\NumNatural())
            ->addRule('nombre', new Validate\Rule\Alpha(array(' ')))
            ->addRule('nombre', new Validate\Rule\MinLength(2))
            ->addRule('nombre', new Validate\Rule\MaxLength(64))
            ->addRule('descripcion', new Validate\Rule\MaxLength(512));
        $req = $this->request;
        $data = array_merge(array('idPat' => $idPat), $req->post());
        if (!$vdt->validate($data)) {
            throw new TurnbackException($vdt->getErrors());
        }
        $patrulla = Patrulla::findOrFail($idPat);
        $patrulla->nombre = $vdt->getData('nombre');
        $patrulla->descripcion = $vdt->getData('descripcion');
        $patrulla->save();
        $this->flash('success', 'Los datos del grupo de moderación fueron modificados exitosamente.');
        $this->redirectTo('shwAdmPatrull');
    }

    public function eliminar($idPat) {
        $vdt = new Validate\QuickValidator(array($this, 'notFound'));
        $vdt->test($idPat, new Validate\Rule\NumNatural());
        $patrulla = Patrulla::with('moderadores')->findOrFail($idPat);
        if (!$patrulla->moderadores->isEmpty()) {
            throw new TurnbackException('Para eliminar una patrulla no debe haber moderadores dentro de esta.');
        }
        $patrulla->delete();
        $this->flash('success', 'La patrulla ha sido eliminada exitosamente.');
        $this->redirectTo('shwAdmPatrull');
    }

    public function verCambiarPoder($idPat) {
        $vdt = new Validate\QuickValidator(array($this, 'notFound'));
        $vdt->test($idPat, new Validate\Rule\NumNatural());
        $patrulla = Patrulla::findOrFail($idPat);
        $datosPat = $patrulla->toArray();
        $datosPat['poderes'] = $patrulla->poderes()->lists('poder_id');
        $poderes = Poder::all()->toArray();
        $this->render('admin/gestionar-poderes.twig', array('patrulla' => $datosPat,
                                                            'poderes' => $poderes));
    }

    public function cambiarPoder($idPat) {
        $vdt = new Validate\Validator();
        $vdt->addRule('idPat', new Validate\Rule\NumNatural())
            ->addRule('poderes', new Validate\Rule\Regex('/^\[\d*(?:,\d+)*\]$/'));
        $req = $this->request;
        $data = array_merge(array('idPat' => $idPat), $req->post());
        if (!$vdt->validate($data)) {
            throw new TurnbackException($vdt->getErrors());
        }
        $patrulla = Patrulla::findOrFail($idPat);
        $poderes = json_decode($vdt->getData('poderes'));
        $patrulla->poderes()->sync($poderes);
        $this->flash('success', 'Los permisos del grupo de moderación fueron modificados exitosamente.');
        $this->redirectTo('shwAdmPatrull');
    }

    public function verAdminModeradores($idPat) {
        $vdt = new Validate\QuickValidator(array($this, 'notFound'));
        $vdt->test($idPat, new Validate\Rule\NumNatural());
        $patrulla = Patrulla::with('moderadores')->findOrFail($idPat);
        $this->render('admin/moderadores.twig', array('patrulla' => $patrulla->toArray(),
                                                      'moderadores' => $patrulla->moderadores->toArray()));
    }

    public function adminModeradores($idPat) {
        $vdt = new Validate\Validator();
        $vdt->addRule('idPat', new Validate\Rule\NumNatural())
            ->addRule('salientes', new Validate\Rule\Regex('/^\[\d*(?:,\d+)*\]$/'));
        $req = $this->request;
        $data = array_merge(array('idPat' => $idPat), $req->post());
        if (!$vdt->validate($data)) {
            throw new TurnbackException($vdt->getErrors());
        }
        $patrulla = Patrulla::findOrFail($idPat);
        $salientes = json_decode($vdt->getData('salientes'));
        $patrulla->moderadores()->whereIn('id', $salientes)->update(['patrulla_id' => null]);
        $this->flash('success', 'Los moderadores han sido removidos de la patrulla exitosamente.');
        $this->redirectTo('shwAdmModerad');
    }

}
