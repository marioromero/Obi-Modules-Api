<?php

namespace Modules\Core\app\Support\Traits;

use DomainException;
use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait DeletionStrategies
{
    /**
     * Emula SQL RESTRICT: impide el borrado si hay dependencias.
     *
     * @param  class-string  $relationModel  Modelo relacionado
     * @param  string        $foreignKey     Llave foránea en el modelo relacionado
     * @return $this
     *
     * @throws DomainException
     */
    public function restrictOn(string $relationModel, string $foreignKey): self
    {
        $id    = $this->getKey();
        $count = $relationModel::where($foreignKey, $id)->count();

        if ($count > 0) {
            // Nombre del modelo actual, en minúscula
            $modelName = Str::lower(class_basename($this));

            // Nombre del modelo relacionado, y pluralizado según el count
            $relBase   = Str::lower(class_basename($relationModel));
            $relName   = Str::plural($relBase, $count);

            // Frase “existe(n) X registros”
            $existStr = $count === 1
                ? "existe 1 {$relBase}"
                : "existen {$count} {$relName}";

            throw new DomainException(
                "No se puede eliminar este {$modelName} porque {$existStr} que lo referencian."
            );
        }

        return $this;
    }

    /**
     * Emula SQL SET NULL: pone a null la FK en los hijos antes de borrar.
     *
     * @param  class-string  $relationModel
     * @param  string        $foreignKey
     * @return $this
     */
    public function setNullOn(string $relationModel, string $foreignKey): self
    {
        $relationModel::where($foreignKey, $this->getKey())
                      ->update([$foreignKey => null]);

        return $this;
    }

    /**
     * Emula SQL CASCADE: borra los registros hijos antes de borrar el padre.
     *
     * @param  class-string  $relationModel
     * @param  string        $foreignKey
     * @return $this
     */
    public function cascadeOn(string $relationModel, string $foreignKey): self
    {
        $relationModel::where($foreignKey, $this->getKey())->delete();

        return $this;
    }
}
