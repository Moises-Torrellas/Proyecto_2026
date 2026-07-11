<section class="contenedor_modal" id="mini_modal_cropper" style="z-index: 10000; display: none;">
    <div class="modal modal_pequeno" id="mini_modal" style="width: 400px; max-width: 90%;">
        <div class="cabecera_modal">
            <h2 class="titulo_modal">Ajustar Foto</h2>
            <a type="button" class="cerrar_modal" id="cerrar_mini_modal">&times;</a>
        </div>
        <div class="contenido_modal" style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
            <div class="cropper-container-wrapper" style="width: 100%; max-height: 60vh; background: var(--fondo-principal); display: flex; justify-content: center; align-items: center; overflow: hidden;">
                <img id="cropper_image" src="" alt="Imagen a recortar" style="max-width: 100%; max-height: 60vh; display: block;">
            </div>
            <div class="botones_cropper" style="display: flex; gap: 15px; width: 100%; justify-content: center; margin-top: 10px;">
                <button type="button" class="btn btn_verde" id="btn_cancelar_crop">Cancelar</button>
                <button type="button" class="btn btn_azul" id="btn_recortar">Aceptar</button>
            </div>
        </div>
    </div>
</section>
