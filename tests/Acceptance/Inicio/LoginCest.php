<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class LoginCest
{
    public function _before(AcceptanceTester $I): void
    {
        // Este método se ejecuta antes de cada prueba. Puedes dejarlo vacío por ahora.
    }

    public function probarLoginExitoso(AcceptanceTester $I): void
    {
        // 1. Va a la página de login (Ajusta la ruta si es necesario)
        $I->amOnPage('/'); 
        
        // 2. Espera a que el formulario HTML aparezca en pantalla
        $I->waitForElement('input[name="cedula"]', 5);

        // 3. Llena los datos y hace clic
        $I->fillField('cedula', '12345678'); 
        $I->fillField('contraseña', 'Admin456$'); 
        $I->click('Iniciar Sesión'); 
        
        // 4. Como es AJAX, espera hasta 5 segundos a que aparezca el mensaje
        $I->waitForText('Inicio Validado', 5); 
    }
}