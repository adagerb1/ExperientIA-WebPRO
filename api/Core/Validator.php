<?php
namespace Core;

/**
 * Sanitización y validación de inputs. La protección real contra SQL
 * injection son los prepared statements (Database); esto añade limpieza
 * de contenido, validación de formato y normalización.
 */
final class Validator
{
    private array $data;
    private array $limpio = [];
    private array $errores = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    /** Texto plano: quita etiquetas, control chars y recorta longitud. */
    public function text(string $campo, bool $req = false, int $max = 255): self
    {
        $v = trim((string) ($this->data[$campo] ?? ''));
        $v = strip_tags($v);
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
        $v = mb_substr($v, 0, $max);
        if ($req && $v === '') {
            $this->errores[$campo] = 'requerido';
        }
        $this->limpio[$campo] = $v;
        return $this;
    }

    /** Texto largo con saltos de línea permitidos. */
    public function textarea(string $campo, bool $req = false, int $max = 5000): self
    {
        $v = trim((string) ($this->data[$campo] ?? ''));
        $v = strip_tags($v);
        $v = mb_substr($v, 0, $max);
        if ($req && $v === '') {
            $this->errores[$campo] = 'requerido';
        }
        $this->limpio[$campo] = $v;
        return $this;
    }

    public function email(string $campo, bool $req = false): self
    {
        $v = mb_strtolower(trim((string) ($this->data[$campo] ?? '')));
        if ($v !== '' && ! filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errores[$campo] = 'formato';
        }
        if ($req && $v === '') {
            $this->errores[$campo] = 'requerido';
        }
        $this->limpio[$campo] = $v;
        return $this;
    }

    /** Teléfono E.164 sin "+" (formato wa.me). */
    public function phone(string $campo, bool $req = false): self
    {
        $v = preg_replace('/\D+/', '', (string) ($this->data[$campo] ?? ''));
        if ($v !== '' && (strlen($v) < 8 || strlen($v) > 15)) {
            $this->errores[$campo] = 'formato';
        }
        if ($req && $v === '') {
            $this->errores[$campo] = 'requerido';
        }
        $this->limpio[$campo] = $v;
        return $this;
    }

    public function in(string $campo, array $opciones, bool $req = false): self
    {
        $v = (string) ($this->data[$campo] ?? '');
        if ($v !== '' && ! in_array($v, $opciones, true)) {
            $this->errores[$campo] = 'invalido';
        }
        if ($req && $v === '') {
            $this->errores[$campo] = 'requerido';
        }
        $this->limpio[$campo] = $v;
        return $this;
    }

    public function int(string $campo, ?int $min = null, ?int $max = null, bool $req = false): self
    {
        $raw = $this->data[$campo] ?? null;
        if ($raw === null || $raw === '') {
            if ($req) {
                $this->errores[$campo] = 'requerido';
            }
            $this->limpio[$campo] = null;
            return $this;
        }
        $v = (int) $raw;
        if ($min !== null && $v < $min) $this->errores[$campo] = 'rango';
        if ($max !== null && $v > $max) $this->errores[$campo] = 'rango';
        $this->limpio[$campo] = $v;
        return $this;
    }

    public function country(string $campo, bool $req = false): self
    {
        $v = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', (string) ($this->data[$campo] ?? '')), 0, 2));
        if ($req && strlen($v) !== 2) {
            $this->errores[$campo] = 'requerido';
        }
        $this->limpio[$campo] = $v;
        return $this;
    }

    /** Honeypot anti-spam: si trae contenido, es un bot. */
    public function honeypot(string $campo = 'website'): self
    {
        if (! empty($this->data[$campo])) {
            Response::error('Solicitud rechazada.', 422);
        }
        return $this;
    }

    public function fails(): bool
    {
        return ! empty($this->errores);
    }

    public function failOrValidated(): array
    {
        if ($this->fails()) {
            Response::error('Revise los campos marcados.', 422, ['campos' => array_keys($this->errores)]);
        }
        return $this->limpio;
    }

    public function validated(): array
    {
        return $this->limpio;
    }

    public function errors(): array
    {
        return $this->errores;
    }
}
