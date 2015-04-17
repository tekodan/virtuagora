<?php use Illuminate\Database\Eloquent\Model as Eloquent;

class Contenido extends Eloquent {
    use Illuminate\Database\Eloquent\SoftDeletingTrait;

    //$table = 'contenidos';

    protected $dates = ['deleted_at'];
    protected $visible = ['id', 'titulo', 'contenible_id', 'contenible_type', 'impulsor_id', 'puntos', 'created_at',
                          'categoria', 'autor', 'contenible'];
    protected $appends = ['link'];
    protected $with = ['autor', 'categoria'];

    public function contenible() {
        return $this->morphTo();
    }

    public function autor() {
        return $this->belongsTo('Usuario');
    }

    public function impulsor() {
        return $this->belongsTo('Partido');
    }

    public function categoria() {
        return $this->belongsTo('Categoria');
    }

    public function tags() {
        return $this->belongsToMany('Tag');
    }

    public function getLinkAttribute() {
        $name = 'shw' . substr($this->attributes['contenible_type'], 0, 7);
        $attr = ['id' . substr($this->attributes['contenible_type'], 0, 3) => $this->attributes['contenible_id']];
        return Slim\Slim::getInstance()->urlFor($name, $attr);
    }
}
