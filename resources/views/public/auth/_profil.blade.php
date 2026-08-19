<div>
    <label for="jenis_pemohon" class="block text-sm font-medium text-gray-700 mb-1">Jenis Pemohon</label>
    <select id="jenis_pemohon" name="jenis_pemohon" x-model="jenisPemohon" required
        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
        @error('jenis_pemohon') aria-invalid="true" aria-describedby="jenis-pemohon-error" @enderror>
        <option value="publik">Publik / Perorangan</option>
        <option value="instansi">Instansi / Organisasi</option>
    </select>
    @error('jenis_pemohon') <p id="jenis-pemohon-error" class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div x-show="jenisPemohon === 'instansi'" x-cloak>
    <label for="nama_instansi" class="block text-sm font-medium text-gray-700 mb-1">Nama Instansi</label>
    <input id="nama_instansi" type="text" name="nama_instansi" value="{{ old('nama_instansi') }}"
        autocomplete="organization" placeholder="Nama instansi/organisasi"
        :required="jenisPemohon === 'instansi'" :aria-required="(jenisPemohon === 'instansi').toString()"
        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
        @error('nama_instansi') aria-invalid="true" aria-describedby="nama-instansi-error" @enderror>
    @error('nama_instansi') <p id="nama-instansi-error" class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Pemohon</label>
    <input id="nama" type="text" name="nama" value="{{ old('nama') }}" required autocomplete="name"
        placeholder="Nama lengkap"
        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
        @error('nama') aria-invalid="true" aria-describedby="nama-error" @enderror>
    @error('nama') <p id="nama-error" class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-gray-400">(opsional)</span></label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email"
        placeholder="contoh@email.com"
        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
    @error('email') <p id="email-error" class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>
