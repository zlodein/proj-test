<div class="modal-overlay" id="addSlideModal">
    <div class="modal-content">
        <h2 style="margin-bottom: 25px; color: var(--text-dark);">
            <i class="fas fa-layer-group"></i> Выберите тип слайда
        </h2>
        <div class="slide-type-selector">
            <div class="slide-type-card" onclick="addSlideOfType('cover')">
                <i class="fas fa-home"></i>
                <h4>Обложка</h4>
                <p>Первый слайд с названием и ценой</p>
            </div>
            <div class="slide-type-card" onclick="addSlideOfType('description')">
                <i class="fas fa-align-left"></i>
                <h4>Описание</h4>
                <p>Текст с двумя изображениями</p>
            </div>
            <div class="slide-type-card" onclick="addSlideOfType('infrastructure')">
                <i class="fas fa-building"></i>
                <h4>Инфраструктура</h4>
                <p>Текст + 2 изображения</p>
            </div>
            <div class="slide-type-card" onclick="addSlideOfType('features')">
                <i class="fas fa-star"></i>
                <h4>Особенности</h4>
                <p>Список с двумя изображениями</p>
            </div>
            <div class="slide-type-card" onclick="addSlideOfType('location')">
                <i class="fas fa-map-marker-alt"></i>
                <h4>Местоположение</h4>
                <p>Карта с информацией</p>
            </div>
            <div class="slide-type-card" onclick="addSlideOfType('image')">
                <i class="fas fa-image"></i>
                <h4>Изображение</h4>
                <p>Одно большое изображение</p>
            </div>
            <div class="slide-type-card" onclick="addSlideOfType('gallery')">
                <i class="fas fa-images"></i>
                <h4>Галерея</h4>
                <p>3 изображения</p>
            </div>
            <div class="slide-type-card" onclick="addSlideOfType('characteristics')">
                <i class="fas fa-list-ul"></i>
                <h4>Характеристики</h4>
                <p>Таблица с параметрами</p>
            </div>
            <div class="slide-type-card" onclick="addSlideOfType('grid')">
                <i class="fas fa-th"></i>
                <h4>Сетка</h4>
                <p>4 изображения</p>
            </div>
            <div class="slide-type-card" onclick="addSlideOfType('contacts')">
                <i class="fas fa-address-card"></i>
                <h4>Контакты</h4>
                <p>Контактная информация</p>
            </div>
        </div>
        <div style="margin-top: 30px; text-align: center;">
            <button class="btn btn-cancel" onclick="closeAddSlideDialog()" style="background: #95a5a6;">
                <i class="fas fa-times"></i> Отмена
            </button>
        </div>
    </div>
</div>