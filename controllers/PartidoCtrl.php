<?php

class PartidoCtrl extends Controller {

    public function showPartidos() {
        $partidos = Partido::all();
        $this->render('partido/listar.twig', array('partidos' => $partidos->toArray()));
    }

    public function showCrearPartido() {
        $this->render('partido/crear.twig');
    }

    public function crearPartido() {
        $validator = new Augusthur\Validation\Validator();
        $validator
            ->add_rule('nombre', new Augusthur\Validation\Rule\Alpha(array(' ')))
            ->add_rule('nombre', new Augusthur\Validation\Rule\MinLength(2))
            ->add_rule('nombre', new Augusthur\Validation\Rule\MaxLength(64))
            ->add_rule('acronimo', new Augusthur\Validation\Rule\Alpha())
            ->add_rule('acronimo', new Augusthur\Validation\Rule\MinLength(2))
            ->add_rule('acronimo', new Augusthur\Validation\Rule\MaxLength(8))
            ->add_rule('descripcion', new Augusthur\Validation\Rule\MaxLength(512));
        $req = $this->request;
        if (!$validator->is_valid($req->post())) {
            throw (new TurnbackException())->setErrors($validator->get_errors());
        }
        $usuario = $this->session->getUser();
        if ($usuario->partido_id) {
            throw (new TurnbackException())->setErrors(array('No es posible crear un partido si ya está afilado a otro.'));
        }
        $partido = new Partido;
        $partido->nombre = $req->post('nombre');
        $partido->acronimo = $req->post('acronimo');
        $partido->descripcion = $req->post('descripcion');
        $partido->creador_id = $this->session->user('id');
        $partido->creador()->associate($usuario);
        $partido->save();
        $this->crearImagen($partido->id, $partido->nombre);
        $this->redirect($req->getRootUri().'/partidos');
    }

    private function crearImagen($id, $nombre) {
        $dir = 'img/partidos/' . $id;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $hash = md5(strtolower(trim($nombre)));
        foreach (array(32, 64, 160) as $res) {
            $ch = curl_init('http://www.gravatar.com/avatar/'.$hash.'?d=identicon&f=y&s='.$res);
            $fp = fopen($dir . '/' . $res . '.png', 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }
    }

    public function afiliarPartido($idPar) {
        $validator = new Augusthur\Validation\Validator();
        $validator
            ->add_rule('idPar', new Augusthur\Validation\Rule\NumNatural());
        if (!$validator->is_valid(array('idPar' => $idPar))) {
            throw (new TurnbackException())->setErrors($validator->get_errors());
        }
        $partido = Partido::findOrFail($idPar);
        $usuario = $this->session->getUser();
        if ($usuario->partido) {
            throw (new TurnbackException())->setErrors(array('Usted ya está afiliado a otro partido.'));
        }
        $usuario->partido()->associate($partido);
        $usuario->save();
        $this->redirect($this->request->getRootUri().'/partido/'.$idPar);
    }

    public function cambiarImagen($idPar) {
        $dir = 'img/partidos/' . $idPar;
        if (!is_dir($dir)) {
            mkdir('$dir', 0777, true);
        }
        $storage = new \Upload\Storage\FileSystem($dir, true);
        $file = new \Upload\File('imagen', $storage);
        $filename = 'original' . $file->getExtension();
        $file->setName($filename);
        $file->addValidations(array(
            new \Upload\Validation\Mimetype(array('image/png', 'image/jpg', 'image/jpeg', 'image/gif')),
            new \Upload\Validation\Size('1M')
        ));
        $file->upload();
        foreach (array(32, 64, 160) as $res) {
            $image = new ZebraImage();
            $image->source_path = $dir . '/' . $filename;
            $image->target_path = $dir . '/' . $res . '.jpg';
            $image->jpeg_quality = 85;
            $image->preserve_aspect_ratio = true;
            $image->enlarge_smaller_images = true;
            $image->preserve_time = true;
            $image->resize($res, $res, ZEBRA_IMAGE_CROP_CENTER);
        }
    }

}
