<section>
    <header class="bf-form-section-heading"><span class="bf-eyebrow">Vùng nguy hiểm</span><h2>Xóa tài khoản</h2><p>Sau khi xóa, toàn bộ dữ liệu tài khoản sẽ bị loại bỏ vĩnh viễn.</p></header>
    <button class="bf-button bf-button--danger" type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">Xóa tài khoản</button>
    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="bf-modal-form">
            @csrf
            @method('delete')
            <span class="bf-eyebrow">Xác nhận thao tác</span><h2>Bạn chắc chắn muốn xóa tài khoản?</h2><p>Thao tác này không thể hoàn tác. Hãy nhập mật khẩu để xác nhận.</p>
            <label class="bf-field"><span>Mật khẩu</span><input id="delete-account-password" name="password" type="password" placeholder="Nhập mật khẩu"><x-input-error :messages="$errors->userDeletion->get('password')" class="bf-field__error" /></label>
            <div class="bf-modal-form__actions"><button class="bf-button bf-button--ghost" type="button" x-on:click="$dispatch('close')">Hủy</button><button class="bf-button bf-button--danger" type="submit">Xóa vĩnh viễn</button></div>
        </form>
    </x-modal>
</section>
