@php
    $daftarProvinsi = [
        'Aceh',
        'Bali',
        'Banten',
        'Bengkulu',
        'DI Yogyakarta',
        'DKI Jakarta',
        'Gorontalo',
        'Jambi',
        'Jawa Barat',
        'Jawa Tengah',
        'Jawa Timur',
        'Kalimantan Barat',
        'Kalimantan Selatan',
        'Kalimantan Tengah',
        'Kalimantan Timur',
        'Kalimantan Utara',
        'Kepulauan Bangka Belitung',
        'Kepulauan Riau',
        'Lampung',
        'Maluku',
        'Maluku Utara',
        'Nusa Tenggara Barat',
        'Nusa Tenggara Timur',
        'Papua',
        'Papua Barat',
        'Papua Barat Daya',
        'Papua Pegunungan',
        'Papua Selatan',
        'Papua Tengah',
        'Riau',
        'Sulawesi Barat',
        'Sulawesi Selatan',
        'Sulawesi Tengah',
        'Sulawesi Tenggara',
        'Sulawesi Utara',
        'Sumatera Barat',
        'Sumatera Selatan',
        'Sumatera Utara',
    ];

    /*
     * Kabupaten/kota yang sudah tersedia.
     * Untuk provinsi yang belum tersedia lengkap,
     * pengguna tetap dapat mengetik kabupaten/kota secara manual.
     */
    $kabupatenKota = [
        'Sumatera Utara' => [
            'Kabupaten Asahan',
            'Kabupaten Batu Bara',
            'Kabupaten Dairi',
            'Kabupaten Deli Serdang',
            'Kabupaten Humbang Hasundutan',
            'Kabupaten Karo',
            'Kabupaten Labuhanbatu',
            'Kabupaten Labuhanbatu Selatan',
            'Kabupaten Labuhanbatu Utara',
            'Kabupaten Langkat',
            'Kabupaten Mandailing Natal',
            'Kabupaten Nias',
            'Kabupaten Nias Barat',
            'Kabupaten Nias Selatan',
            'Kabupaten Nias Utara',
            'Kabupaten Padang Lawas',
            'Kabupaten Padang Lawas Utara',
            'Kabupaten Pakpak Bharat',
            'Kabupaten Samosir',
            'Kabupaten Serdang Bedagai',
            'Kabupaten Simalungun',
            'Kabupaten Tapanuli Selatan',
            'Kabupaten Tapanuli Tengah',
            'Kabupaten Tapanuli Utara',
            'Kabupaten Toba',
            'Kota Binjai',
            'Kota Gunungsitoli',
            'Kota Medan',
            'Kota Padangsidimpuan',
            'Kota Pematangsiantar',
            'Kota Sibolga',
            'Kota Tanjungbalai',
            'Kota Tebing Tinggi',
        ],

        'DKI Jakarta' => [
            'Kabupaten Kepulauan Seribu',
            'Kota Jakarta Barat',
            'Kota Jakarta Pusat',
            'Kota Jakarta Selatan',
            'Kota Jakarta Timur',
            'Kota Jakarta Utara',
        ],

        'DI Yogyakarta' => [
            'Kabupaten Bantul',
            'Kabupaten Gunungkidul',
            'Kabupaten Kulon Progo',
            'Kabupaten Sleman',
            'Kota Yogyakarta',
        ],

        'Banten' => [
            'Kabupaten Lebak',
            'Kabupaten Pandeglang',
            'Kabupaten Serang',
            'Kabupaten Tangerang',
            'Kota Cilegon',
            'Kota Serang',
            'Kota Tangerang',
            'Kota Tangerang Selatan',
        ],

        'Jawa Barat' => [
            'Kabupaten Bandung',
            'Kabupaten Bandung Barat',
            'Kabupaten Bekasi',
            'Kabupaten Bogor',
            'Kabupaten Ciamis',
            'Kabupaten Cianjur',
            'Kabupaten Cirebon',
            'Kabupaten Garut',
            'Kabupaten Indramayu',
            'Kabupaten Karawang',
            'Kabupaten Kuningan',
            'Kabupaten Majalengka',
            'Kabupaten Pangandaran',
            'Kabupaten Purwakarta',
            'Kabupaten Subang',
            'Kabupaten Sukabumi',
            'Kabupaten Sumedang',
            'Kabupaten Tasikmalaya',
            'Kota Bandung',
            'Kota Banjar',
            'Kota Bekasi',
            'Kota Bogor',
            'Kota Cimahi',
            'Kota Cirebon',
            'Kota Depok',
            'Kota Sukabumi',
            'Kota Tasikmalaya',
        ],

        'Jawa Tengah' => [
            'Kabupaten Banyumas',
            'Kabupaten Batang',
            'Kabupaten Blora',
            'Kabupaten Boyolali',
            'Kabupaten Brebes',
            'Kabupaten Cilacap',
            'Kabupaten Demak',
            'Kabupaten Grobogan',
            'Kabupaten Jepara',
            'Kabupaten Karanganyar',
            'Kabupaten Kebumen',
            'Kabupaten Kendal',
            'Kabupaten Klaten',
            'Kabupaten Kudus',
            'Kabupaten Magelang',
            'Kabupaten Pati',
            'Kabupaten Pekalongan',
            'Kabupaten Pemalang',
            'Kabupaten Purbalingga',
            'Kabupaten Purworejo',
            'Kabupaten Rembang',
            'Kabupaten Semarang',
            'Kabupaten Sragen',
            'Kabupaten Sukoharjo',
            'Kabupaten Tegal',
            'Kabupaten Temanggung',
            'Kabupaten Wonogiri',
            'Kabupaten Wonosobo',
            'Kota Magelang',
            'Kota Pekalongan',
            'Kota Salatiga',
            'Kota Semarang',
            'Kota Surakarta',
            'Kota Tegal',
        ],

        'Jawa Timur' => [
            'Kabupaten Bangkalan',
            'Kabupaten Banyuwangi',
            'Kabupaten Blitar',
            'Kabupaten Bojonegoro',
            'Kabupaten Bondowoso',
            'Kabupaten Gresik',
            'Kabupaten Jember',
            'Kabupaten Jombang',
            'Kabupaten Kediri',
            'Kabupaten Lamongan',
            'Kabupaten Lumajang',
            'Kabupaten Madiun',
            'Kabupaten Magetan',
            'Kabupaten Malang',
            'Kabupaten Mojokerto',
            'Kabupaten Nganjuk',
            'Kabupaten Ngawi',
            'Kabupaten Pacitan',
            'Kabupaten Pamekasan',
            'Kabupaten Pasuruan',
            'Kabupaten Ponorogo',
            'Kabupaten Probolinggo',
            'Kabupaten Sampang',
            'Kabupaten Sidoarjo',
            'Kabupaten Situbondo',
            'Kabupaten Sumenep',
            'Kabupaten Trenggalek',
            'Kabupaten Tuban',
            'Kabupaten Tulungagung',
            'Kota Batu',
            'Kota Blitar',
            'Kota Kediri',
            'Kota Madiun',
            'Kota Malang',
            'Kota Mojokerto',
            'Kota Pasuruan',
            'Kota Probolinggo',
            'Kota Surabaya',
        ],
    ];

    $oldProvinsi = old('provinsi', '');
    $oldKabupaten = old('kabupaten_kota', '');

    $daftarKabupatenOld = $kabupatenKota[$oldProvinsi] ?? [];

    $oldKabupatenAdaDiDropdown =
        $oldKabupaten !== ''
        && in_array($oldKabupaten, $daftarKabupatenOld, true);

    $oldKabupatenManual =
        $oldKabupaten !== ''
        && ! $oldKabupatenAdaDiDropdown;
@endphp


{{-- ===================================================== --}}
{{-- 1. DATA DIRI --}}
{{-- ===================================================== --}}

<section class="space-y-4" aria-labelledby="data-diri-title">

    <div>
        <h3 id="data-diri-title" class="text-base font-bold text-gray-800">
            1. Data Diri
        </h3>

        <p class="mt-1 text-sm text-gray-500">
            Isi data diri yang benar agar kami mudah menghubungi Anda.
        </p>
    </div>

    <div>
        <label for="nama" class="mb-1 block text-sm font-medium text-gray-700">
            Nama Lengkap
            <span class="text-red-500">*</span>
        </label>

        <input
            id="nama"
            type="text"
            name="nama"
            value="{{ old('nama') }}"
            required
            autocomplete="name"
            placeholder="Contoh: Siti Aisyah"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none transition
                focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                @error('nama') border-red-400 @enderror"
        >

        @error('nama')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if(($tampilkanNomorWhatsapp ?? false))
        <div>
            <label for="no_hp" class="mb-1 block text-sm font-medium text-gray-700">
                Nomor WhatsApp
                <span class="text-red-500">*</span>
            </label>

            <input
                id="no_hp"
                type="tel"
                name="no_hp"
                value="{{ old('no_hp') }}"
                required
                inputmode="tel"
                autocomplete="tel"
                placeholder="Contoh: 0812 3456 7890"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none transition
                    focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
            >

            <p class="mt-1 text-xs text-gray-500">
                Kode OTP akan dikirim ke nomor WhatsApp ini.
            </p>

            @error('no_hp')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div>
        <label for="email" class="mb-1 block text-sm font-medium text-gray-700">
            Email
            <span class="font-normal text-gray-400">(jika ada)</span>
        </label>

        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            autocomplete="email"
            placeholder="Contoh: nama@email.com"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none transition
                focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                @error('email') border-red-400 @enderror"
        >

        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

</section>


{{-- ===================================================== --}}
{{-- 2. INFORMASI PEMOHON --}}
{{-- ===================================================== --}}

<section
    class="space-y-4 border-t border-gray-100 pt-6"
    aria-labelledby="informasi-pemohon-title"
>
    <div>
        <h3 id="informasi-pemohon-title" class="text-base font-bold text-gray-800">
            2. Informasi Pemohon
        </h3>

        <p class="mt-1 text-sm text-gray-500">
            Pilih kategori yang paling sesuai dengan Anda.
        </p>
    </div>

    <div>
        <label for="jenis_pemohon" class="mb-1 block text-sm font-medium text-gray-700">
            Anda mendaftar sebagai apa?
            <span class="text-red-500">*</span>
        </label>

        <select
            id="jenis_pemohon"
            name="jenis_pemohon"
            x-model="jenisPemohon"
            required
            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 outline-none transition
                focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                @error('jenis_pemohon') border-red-400 @enderror"
        >
            <option value="">Pilih kategori Anda</option>
            <option value="masyarakat_umum">Masyarakat Umum</option>
            <option value="pelajar_mahasiswa">Pelajar / Mahasiswa</option>
            <option value="akademisi_peneliti">Akademisi / Peneliti</option>
            <option value="instansi_pemerintah">Instansi Pemerintah</option>
            <option value="bumn_bumd">BUMN / BUMD</option>
            <option value="perusahaan_swasta">Perusahaan Swasta</option>
            <option value="organisasi_lembaga">Organisasi / Lembaga</option>
            <option value="lainnya">Lainnya</option>
        </select>

        @error('jenis_pemohon')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div
        x-show="jenisPemohon && jenisPemohon !== 'masyarakat_umum'"
        x-cloak
        x-transition.opacity
    >
        <label for="nama_instansi" class="mb-1 block text-sm font-medium text-gray-700">
            Nama Sekolah / Kampus / Instansi / Lembaga
            <span class="text-red-500">*</span>
        </label>

        <input
            id="nama_instansi"
            type="text"
            name="nama_instansi"
            value="{{ old('nama_instansi') }}"
            :required="jenisPemohon && jenisPemohon !== 'masyarakat_umum'"
            autocomplete="organization"
            placeholder="Contoh: Universitas Sumatera Utara"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none transition
                focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                @error('nama_instansi') border-red-400 @enderror"
        >

        <p class="mt-1 text-xs text-gray-500">
            Isi nama tempat Anda belajar, bekerja, atau organisasi yang Anda wakili.
        </p>

        @error('nama_instansi')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

</section>


{{-- ===================================================== --}}
{{-- 3. ALAMAT --}}
{{-- ===================================================== --}}

<section
    class="space-y-4 border-t border-gray-100 pt-6"
    aria-labelledby="alamat-title"

    x-data="{
        provinsi: @js($oldProvinsi),

        kabupatenPilihan: @js(
            $oldKabupatenAdaDiDropdown
                ? $oldKabupaten
                : ($oldKabupatenManual ? '__lainnya' : '')
        ),

        kabupatenManual: @js(
            $oldKabupatenManual
                ? $oldKabupaten
                : ''
        ),

        daftarKabupaten: @js($kabupatenKota),

        get pilihanKabupaten() {
            return this.daftarKabupaten[this.provinsi] ?? []
        },

        get punyaDaftarKabupaten() {
            return this.pilihanKabupaten.length > 0
        },

        get gunakanManual() {
            return Boolean(
                this.provinsi &&
                (
                    ! this.punyaDaftarKabupaten ||
                    this.kabupatenPilihan === '__lainnya'
                )
            )
        },

        get nilaiKabupaten() {
            return this.gunakanManual
                ? this.kabupatenManual
                : this.kabupatenPilihan
        },

        ubahProvinsi() {
            this.kabupatenPilihan = ''
            this.kabupatenManual = ''
        }
    }"
>
    <div>
        <h3 id="alamat-title" class="text-base font-bold text-gray-800">
            3. Alamat
        </h3>

        <p class="mt-1 text-sm text-gray-500">
            Gunakan alamat tempat tinggal atau alamat instansi Anda.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">

        {{-- Provinsi --}}
        <div>
            <label for="provinsi" class="mb-1 block text-sm font-medium text-gray-700">
                Provinsi
                <span class="text-red-500">*</span>
            </label>

            <select
                id="provinsi"
                name="provinsi"
                x-model="provinsi"
                @change="ubahProvinsi()"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 outline-none transition
                    focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                    @error('provinsi') border-red-400 @enderror"
            >
                <option value="">Pilih provinsi</option>

                @foreach($daftarProvinsi as $item)
                    <option value="{{ $item }}">
                        {{ $item }}
                    </option>
                @endforeach
            </select>

            @error('provinsi')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>


        {{-- Kabupaten / Kota --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">
                Kabupaten/Kota
                <span class="text-red-500">*</span>
            </label>

            {{-- Belum pilih provinsi --}}
            <div
                x-show="!provinsi"
                class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-400"
            >
                Pilih provinsi terlebih dahulu
            </div>

            {{-- Dropdown --}}
            <select
                x-show="provinsi && punyaDaftarKabupaten"
                x-cloak
                x-model="kabupatenPilihan"
                :required="provinsi && punyaDaftarKabupaten && !gunakanManual"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 outline-none transition
                    focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
            >
                <option value="">Pilih kabupaten/kota</option>

                <template
                    x-for="wilayah in pilihanKabupaten"
                    :key="wilayah"
                >
                    <option
                        :value="wilayah"
                        x-text="wilayah"
                    ></option>
                </template>

                <option value="__lainnya">
                    Kabupaten/Kota lainnya
                </option>
            </select>

            {{-- Manual --}}
            <div
                x-show="gunakanManual"
                x-cloak
                class="space-y-1"
            >
                <input
                    type="text"
                    x-model="kabupatenManual"
                    :required="gunakanManual"
                    placeholder="Tulis nama kabupaten/kota Anda"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none transition
                        focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                        @error('kabupaten_kota') border-red-400 @enderror"
                >

                <p class="text-xs text-gray-500">
                    Ketik nama kabupaten atau kota sesuai alamat Anda.
                </p>
            </div>

            {{-- Value asli yang dikirim ke Laravel --}}
            <input
                type="hidden"
                name="kabupaten_kota"
                :value="nilaiKabupaten"
            >

            @error('kabupaten_kota')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

    </div>


    {{-- Alamat lengkap --}}
    <div>
        <label for="alamat_lengkap" class="mb-1 block text-sm font-medium text-gray-700">
            Alamat Lengkap
            <span class="text-red-500">*</span>
        </label>

        <textarea
            id="alamat_lengkap"
            name="alamat_lengkap"
            rows="4"
            required
            placeholder="Contoh: Jl. Merdeka, Desa Sibuhuan Julu, Kecamatan Barumun"
            class="w-full resize-y rounded-lg border border-gray-300 px-4 py-2.5 outline-none transition
                focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                @error('alamat_lengkap') border-red-400 @enderror"
        >{{ old('alamat_lengkap') }}</textarea>

        <p class="mt-1 text-xs text-gray-500">
            Cukup tuliskan nama jalan, desa/kelurahan, dan kecamatan.
        </p>

        @error('alamat_lengkap')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

</section>