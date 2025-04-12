<?php

namespace App\Services\Correos;

use App\Mail\Email;

class EmailBuilder
{
    private $estadoNombre;
    private $empleadoNombre;
    private $nombreProyecto;
    private $actionUrl;
    private $logoUrl;
    private $appName;
    private $mensaje;
    private $subject;

    public function setEstadoNombre(string $estadoNombre): self
    {
        $this->estadoNombre = $estadoNombre;
        return $this;
    }

    public function setEmpleadoNombre(string $empleadoNombre): self
    {
        $this->empleadoNombre = $empleadoNombre;
        return $this;
    }

    public function setNombreProyecto(string $nombreProyecto): self
    {
        $this->nombreProyecto = $nombreProyecto;
        return $this;
    }

    public function setActionUrl(string $actionUrl): self
    {
        $this->actionUrl = $actionUrl;
        return $this;
    }

    public function setLogoUrl(string $logoUrl): self
    {
        $this->logoUrl = $logoUrl;
        return $this;
    }

    public function setAppName(string $appName): self
    {
        $this->appName = $appName;
        return $this;
    }

    public function setMensaje(string $mensaje): self
    {
        $this->mensaje = $mensaje;
        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function build(): Email
    {
        $email = new Email();
        $email->setEstadoNombre($this->estadoNombre)
              ->setEmpleadoNombre($this->empleadoNombre)
              ->setNombreProyecto($this->nombreProyecto)
              ->setActionUrl($this->actionUrl)
              ->setLogoUrl($this->logoUrl)
              ->setAppName($this->appName)
              ->setMensaje($this->mensaje)
              ->setSubject($this->subject);
        
        return $email;
    }
}
