<?php

namespace App\Data;

class FiturData
{
    /** Locale-independent metadata: pricing, icons, and access flags never vary by language. */
    private static function meta(): array
    {
        return [
            1  => ['hub' => 'Wealth Hub', 'icon' => '🧭', 'harga' => 29000],
            2  => ['hub' => 'Wealth Hub', 'icon' => '📈', 'harga' => 29000],
            3  => ['hub' => 'Wealth Hub', 'icon' => '📅', 'harga' => 29000],
            4  => ['hub' => 'Wealth Hub', 'icon' => '🏪', 'harga' => 29000],
            5  => ['hub' => 'Social Hub', 'icon' => '💑', 'harga' => 29000],
            6  => ['hub' => 'Social Hub', 'icon' => '🏢', 'harga' => 29000],
            7  => ['hub' => 'Social Hub', 'icon' => '👨‍👧', 'harga' => 29000],
            8  => ['hub' => 'Personal Wellness Hub', 'icon' => '✍️', 'harga' => 29000],
            9  => ['hub' => 'Personal Wellness Hub', 'icon' => '💚', 'harga' => 29000, 'tanya_golongan_darah' => true],
            10 => ['hub' => 'Personal Wellness Hub', 'icon' => '🛡️', 'harga' => 29000],
            11 => ['hub' => 'Personal Wellness Hub', 'icon' => '🎯', 'harga' => 0, 'subscriber_only' => true],
            12 => ['hub' => 'Traffic Driver', 'icon' => '🌙', 'harga' => 0, 'freemium' => true],
        ];
    }

    /** Locale-specific content: name, description, and questions. */
    private static function text(): array
    {
        return [
            'id' => [
                1 => [
                    'nama'      => 'Arah Mata Angin Rezeki',
                    'deskripsi' => 'Analisis arah kompas terbaik berdasarkan elemen lahir dikombinasikan dengan kondisi aliran rezeki saat ini.',
                    'pertanyaan' => [
                        'Apakah akhir-akhir ini Anda merasa rezeki Anda sering macet atau tertahan?',
                        'Apakah Anda sering melakukan negosiasi bisnis di luar kantor atau rumah?',
                        'Apakah Anda merasa posisi duduk atau tempat kerja Anda sekarang menghadap ke tembok atau membelakangi jalan?',
                        'Apakah Anda berencana mencari peluang rezeki di luar kota atau kelurahan tempat tinggal Anda saat ini?',
                        'Apakah waktu produktif Anda lebih banyak di malam hari daripada siang hari?',
                        'Apakah meja atau tempat kerja Anda saat ini menghadap ke arah Selatan atau Barat?',
                        'Apakah Anda lebih sering melakukan transaksi finansial penting di pagi hari dibandingkan waktu lainnya?',
                        'Apakah Anda pernah mengubah arah pintu masuk utama rumah atau kantor karena alasan kelancaran rezeki?',
                        'Apakah aktivitas bisnis atau pekerjaan Anda sebagian besar mengarah ke wilayah Timur kota Anda?',
                        'Apakah lingkungan sekitar tempat tinggal Anda saat ini terasa stagnan atau minim pergerakan bisnis?',
                    ],
                ],
                2 => [
                    'nama'      => 'Potensi Bisnis & Gaya Investasi',
                    'deskripsi' => 'Profil risiko investasi dan rekomendasi sektor bisnis berdasarkan elemen shio dan psikologi finansial.',
                    'pertanyaan' => [
                        'Apakah Anda lebih suka keuntungan kecil yang pasti daripada keuntungan besar tapi berisiko?',
                        'Apakah Anda pernah mengalami kerugian besar (boncos) dalam satu tahun terakhir?',
                        'Apakah saat ini Anda memiliki modal menganggur (idle fund) yang siap diputar?',
                        'Apakah Anda tertarik pada bisnis yang melibatkan aset fisik seperti properti atau barang dagangan nyata?',
                        'Apakah Anda sering mengambil keputusan finansial hanya berdasarkan ikut-ikutan tren (FOMO)?',
                        'Apakah Anda saat ini memiliki lebih dari satu sumber penghasilan aktif?',
                        'Apakah Anda lebih nyaman mengelola investasi secara mandiri daripada menyerahkan ke manajer investasi?',
                        'Apakah Anda pernah mencoba bisnis online namun hasilnya tidak sesuai ekspektasi?',
                        'Apakah Anda saat ini memiliki utang atau cicilan aktif yang memengaruhi arus kas bulanan Anda?',
                        'Apakah Anda tertarik merintis usaha di bidang yang sama sekali baru dan belum pernah Anda coba sebelumnya?',
                    ],
                ],
                3 => [
                    'nama'      => 'Hari Baik & Momentum Usaha',
                    'deskripsi' => 'Kalkulasi hari baik 3 bulan ke depan berdasarkan Neptu Weton dan kondisi proyek.',
                    'pertanyaan' => [
                        'Apakah Anda berencana mengeksekusi proyek atau membuka usaha dalam waktu tiga bulan ke depan?',
                        'Apakah bisnis yang akan Anda jalankan ini melibatkan kerja sama dengan mitra atau partner?',
                        'Apakah Anda percaya bahwa pemilihan hari yang salah bisa memengaruhi kelancaran usaha?',
                        'Apakah modal utama usaha ini murni dari dana pribadi Anda sendiri?',
                        'Apakah Anda sedang terikat kontrak aktif yang belum selesai dengan pihak lain?',
                        'Apakah Anda sudah menentukan tanggal target konkret untuk peluncuran proyek atau usaha Anda?',
                        'Apakah ada anggota keluarga dekat yang turut terlibat dalam usaha yang akan Anda jalankan?',
                        'Apakah Anda pernah menunda keputusan besar berkali-kali karena merasa waktunya belum tepat?',
                        'Apakah rencana usaha Anda sudah didiskusikan dan mendapat dukungan penuh dari orang-orang terdekat?',
                        'Apakah Anda berencana melakukan ritual atau doa khusus sebelum memulai usaha baru ini?',
                    ],
                ],
                4 => [
                    'nama'      => 'Arah Tempat Usaha & Ruko',
                    'deskripsi' => 'Analisis feng shui modern — posisi meja, kasir, pintu, dan rekomendasi tata letak berdasarkan elemen lahir pemilik.',
                    'pertanyaan' => [
                        'Apakah tempat usaha atau ruko Anda saat ini posisinya berada di tusuk sate atau tikungan jalan?',
                        'Apakah Anda sering merasa suasana di dalam tempat kerja atau toko terasa panas atau bikin tidak betah?',
                        'Apakah posisi meja kasir atau meja kerja Anda langsung berhadapan dengan pintu masuk utama?',
                        'Apakah ruko atau tempat usaha Anda saat ini statusnya adalah sewa atau kontrak (bukan milik sendiri)?',
                        'Apakah Anda ingin mengubah tata letak interior toko Anda dalam waktu dekat demi menarik pelanggan?',
                        'Apakah terdapat cermin besar yang langsung menghadap pintu masuk utama toko atau tempat usaha Anda?',
                        'Apakah area penyimpanan atau gudang di tempat usaha Anda saat ini berantakan atau sulit diakses?',
                        'Apakah Anda pernah merasakan ada aura kurang nyaman atau "berat" ketika pelanggan pertama kali masuk?',
                        'Apakah warna dominan interior toko atau ruang kerja Anda terasa terlalu gelap atau suram?',
                        'Apakah Anda berencana memasang elemen air (akuarium atau air mancur) di area tempat usaha Anda?',
                    ],
                ],
                5 => [
                    'nama'      => 'Kalkulator Jodoh & Asmara Mendalam',
                    'deskripsi' => 'Kompatibilitas elemen pasangan dan analisis psikologi hubungan. Menghasilkan narasi empatik tentang dinamika pasangan beserta panduan komunikasi.',
                    'pertanyaan' => [
                        'Apakah saat ini hubungan Anda dan pasangan sedang sering diwarnai cekcok karena hal sepele?',
                        'Apakah Anda merasa ada pihak keluarga seperti orang tua atau saudara yang kurang merestui hubungan ini?',
                        'Apakah Anda dan pasangan memiliki perbedaan sifat yang sangat bertolak belakang?',
                        'Apakah Anda berencana membawa hubungan ini ke jenjang pernikahan dalam waktu dekat?',
                        'Apakah Anda sering merasa cemas atau tidak aman (insecure) terhadap kesetiaan pasangan Anda?',
                        'Apakah Anda dan pasangan memiliki perbedaan latar belakang budaya atau agama yang signifikan?',
                        'Apakah komunikasi antara Anda dan pasangan belakangan ini terasa semakin berkurang atau hambar?',
                        'Apakah ada luka atau pengkhianatan di masa lalu yang masih memengaruhi dinamika hubungan Anda saat ini?',
                        'Apakah Anda dan pasangan sudah memiliki rencana keuangan bersama yang disepakati secara terbuka?',
                        'Apakah Anda merasa lebih bahagia dan lebih menjadi diri sendiri ketika bersama pasangan Anda?',
                    ],
                ],
                6 => [
                    'nama'      => 'Radar Toksisitas & Politik Kantor',
                    'deskripsi' => 'Profil karir vs lingkungan kerja saat ini. Menghasilkan strategi menghadapi rekan toksik dan timing tepat untuk keputusan karir besar.',
                    'pertanyaan' => [
                        'Apakah Anda merasa kontribusi kerja Anda di kantor sering tidak dihargai oleh atasan?',
                        'Apakah suasana di lingkungan kerja Anda saat ini terasa kompetitif secara tidak sehat (toxic)?',
                        'Apakah Anda sering merasa cemas atau stres setiap kali hari Senin tiba?',
                        'Apakah Anda berencana untuk resign atau pindah divisi dalam waktu dekat?',
                        'Apakah saat ini Anda memiliki rekan kerja dekat yang benar-benar bisa dipercaya di kantor?',
                        'Apakah Anda memiliki mentor atau senior yang secara aktif membimbing perkembangan karir Anda?',
                        'Apakah Anda pernah merasakan hasil kerja Anda diklaim atau diakui orang lain tanpa kredit yang semestinya?',
                        'Apakah Anda merasa gaji atau kompensasi saat ini tidak sepadan dengan kontribusi yang Anda berikan?',
                        'Apakah ada proyek besar atau peluang promosi yang sedang aktif Anda kejar saat ini?',
                        'Apakah Anda cenderung menghindari konflik langsung dan memilih menyelesaikan masalah secara tidak langsung?',
                    ],
                ],
                7 => [
                    'nama'      => 'Karakter Anak & Pola Asuh',
                    'deskripsi' => 'Analisis elemen lahir anak vs gaya pengasuhan orang tua. Rekomendasi komunikasi yang dipersonalisasi berdasarkan profil energi anak.',
                    'pertanyaan' => [
                        'Apakah anak Anda sering menunjukkan sifat keras kepala atau sulit diatur belakangan ini?',
                        'Apakah Anda merasa kesulitan menebak bakat atau minat utama anak Anda saat ini?',
                        'Apakah anak Anda cenderung lebih pemalu dan tertutup saat berada di lingkungan baru?',
                        'Apakah Anda sering merasa kehabisan kesabaran saat mendampingi anak belajar di rumah?',
                        'Apakah Anda ingin menyesuaikan gaya berkomunikasi Anda agar anak lebih mendengarkan nasihat?',
                        'Apakah anak Anda lebih banyak menghabiskan waktu bermain gadget daripada aktivitas fisik di luar?',
                        'Apakah Anda merasa ada bakat atau kecerdasan unik pada anak yang belum dikembangkan secara optimal?',
                        'Apakah Anda dan pasangan memiliki gaya mendidik yang berbeda sehingga terkadang membingungkan anak?',
                        'Apakah anak Anda pernah menunjukkan tanda kecemasan berlebih di sekolah atau lingkungan sosial baru?',
                        'Apakah Anda secara rutin meluangkan quality time berdua dengan anak tanpa gangguan gadget atau pekerjaan?',
                    ],
                ],
                8 => [
                    'nama'      => 'Audit & Analisis Nama',
                    'deskripsi' => 'Analisis numerologi nama dan elemen lahir. Saran inisial penyeimbang energi.',
                    'pertanyaan' => [
                        'Apakah Anda merasa hidup Anda selalu terasa berat atau sial meskipun sudah bekerja keras?',
                        'Apakah nama yang Anda gunakan di dunia profesional berbeda dengan nama di akta kelahiran?',
                        'Apakah Anda memiliki rencana untuk membuat nama bisnis, nama pena, atau nama panggung baru?',
                        'Apakah Anda sering merasa nama Anda memiliki arti yang kurang selaras dengan doa orang tua?',
                        'Apakah Anda ingin mengetahui inisial huruf tambahan yang bisa menyeimbangkan energi nama Anda?',
                        'Apakah orang lain sering salah mengeja atau salah menyebut nama Anda dalam interaksi sehari-hari?',
                        'Apakah nama Anda memiliki jumlah huruf yang ganjil (misal: 5, 7, atau 9 huruf)?',
                        'Apakah Anda percaya bahwa ada getaran atau energi tertentu yang tersimpan dalam susunan huruf sebuah nama?',
                        'Apakah Anda sedang mengembangkan produk, usaha, atau brand yang membutuhkan nama baru yang berenergi kuat?',
                        'Apakah nama yang Anda pakai sehari-hari sudah mencerminkan kepribadian dan cita-cita yang ingin Anda capai?',
                    ],
                ],
                9 => [
                    'nama'      => 'Kesehatan & Vitalitas Energi Tubuh',
                    'deskripsi' => 'Pemetaan zona tubuh lemah berdasarkan elemen lahir beserta panduan gaya hidup yang selaras dengan ritme energi personal.',
                    'pertanyaan' => [
                        'Apakah Anda sering mengalami gangguan tidur (insomnia) atau sering terbangun di malam hari?',
                        'Apakah area pundak, leher, atau kepala Anda sering terasa tegang dan pegal tanpa alasan medis yang jelas?',
                        'Apakah Anda merasa energi dan stamina Anda cepat habis padahal aktivitas tidak terlalu padat?',
                        'Apakah Anda sedang menjalani program diet atau pemulihan kesehatan saat ini?',
                        'Apakah suasana hati (mood) Anda sering berubah drastis secara tiba-tiba dalam sehari?',
                        'Apakah Anda sering mengonsumsi makanan atau minuman tinggi gula sebagai pelampiasan stres?',
                        'Apakah Anda pernah didiagnosis atau mencurigai adanya gangguan hormon atau metabolisme pada diri Anda?',
                        'Apakah Anda berolahraga secara rutin minimal 3 kali dalam seminggu?',
                        'Apakah Anda sering mengalami gangguan pencernaan (lambung atau usus) tanpa sebab yang jelas secara medis?',
                        'Apakah Anda merasakan perbedaan signifikan pada tingkat energi di musim hujan dibanding musim kemarau?',
                    ],
                ],
                10 => [
                    'nama'      => 'Panduan Ruwat & Mitigasi Sial',
                    'deskripsi' => 'Deteksi posisi Ciong tahun berjalan dan panduan doa/ritual tolak bala.',
                    'pertanyaan' => [
                        'Apakah Anda tahu bahwa Shio atau Zodiak Anda sedang mengalami posisi Ciong (kurang beruntung) tahun ini?',
                        'Apakah dalam kurun waktu tiga bulan terakhir Anda berturut-turut mengalami kesialan nyata?',
                        'Apakah Anda merasa ada energi negatif atau beban tak kasat mata yang menyelimuti pikiran Anda?',
                        'Apakah Anda bersedia meluangkan waktu sepuluh hingga lima belas menit sehari untuk melakukan meditasi mandiri?',
                        'Apakah Anda ingin tahu hari naas Anda di bulan ini agar bisa menunda keputusan-keputusan besar?',
                        'Apakah dalam satu tahun terakhir Anda mengalami kehilangan pekerjaan, perpisahan, atau kehilangan orang terkasih?',
                        'Apakah ada hubungan atau relasi tertentu dalam hidup Anda yang terasa membawa energi negatif secara konsisten?',
                        'Apakah Anda pernah menjalani ritual tolak bala atau ruwatan sebelumnya namun hasilnya belum terasa optimal?',
                        'Apakah Anda mengalami mimpi buruk yang berulang atau fenomena aneh yang mengganggu ketenangan Anda?',
                        'Apakah Anda bersedia mengubah kebiasaan atau rutinitas tertentu demi menetralisir energi negatif dalam hidup Anda?',
                    ],
                ],
                11 => [
                    'nama'      => 'Angka & Warna Keberuntungan Harian',
                    'deskripsi' => 'Output harian berupa 3 angka hoki + 2 warna busana + mantra hari. Khusus subscriber aktif.',
                    'pertanyaan' => [
                        'Apakah Anda sering kebingungan memilih warna pakaian saat ingin menghadiri acara penting?',
                        'Apakah Anda mempercayai bahwa angka tertentu memiliki getaran frekuensi hoki bagi hidup Anda?',
                        'Apakah Anda sering menggunakan kombinasi angka untuk keperluan penting seperti PIN atau plat nomor?',
                        'Apakah Anda ingin menerima pembaruan angka dan warna hoki ini setiap hari langsung di WhatsApp?',
                        'Apakah Anda merasa warna pakaian yang Anda gunakan hari ini memengaruhi tingkat rasa percaya diri Anda?',
                    ],
                ],
                12 => [
                    'nama'      => 'Tafsir Mimpi',
                    'deskripsi' => 'Tafsir dasar dan angka keberuntungan diberikan gratis. Mimpi bermakna buruk membuka paywall Paket Proteksi Diri.',
                    'pertanyaan' => [],
                    'pertanyaan_terbuka' => 'Apa objek atau kejadian spesifik yang paling Anda ingat dari mimpi semalam? (Contoh: digigit ular, gigi copot, rumah kebakaran)',
                ],
            ],

            'en' => [
                1 => [
                    'nama'      => 'Wealth Compass Direction',
                    'deskripsi' => 'Analysis of the best compass direction based on your birth element, combined with your current cash flow conditions.',
                    'pertanyaan' => [
                        'Have you recently felt that your income is often stuck or delayed?',
                        'Do you often conduct business negotiations outside your office or home?',
                        'Does your current seating or workspace face a wall or have its back to a road?',
                        'Are you planning to look for income opportunities outside your current city or sub-district?',
                        'Are you more productive at night than during the day?',
                        'Does your desk or workspace currently face South or West?',
                        'Do you usually carry out important financial transactions in the morning more than at other times?',
                        'Have you ever changed the direction of your home or office\'s main entrance for the sake of better income flow?',
                        'Is most of your business or work activity directed toward the Eastern part of your city?',
                        'Does the environment around your current residence feel stagnant or lacking in business movement?',
                    ],
                ],
                2 => [
                    'nama'      => 'Business Potential & Investment Style',
                    'deskripsi' => 'Investment risk profile and business sector recommendations based on shio elements and financial psychology.',
                    'pertanyaan' => [
                        'Do you prefer a small, certain profit over a large but risky one?',
                        'Have you experienced a major financial loss in the past year?',
                        'Do you currently have idle funds ready to be invested?',
                        'Are you interested in businesses involving physical assets like property or tangible goods?',
                        'Do you often make financial decisions just by following trends (FOMO)?',
                        'Do you currently have more than one active source of income?',
                        'Are you more comfortable managing investments independently rather than handing them to an investment manager?',
                        'Have you tried an online business that didn\'t meet your expectations?',
                        'Do you currently have debt or installments that affect your monthly cash flow?',
                        'Are you interested in starting a business in a completely new field you\'ve never tried before?',
                    ],
                ],
                3 => [
                    'nama'      => 'Auspicious Days & Business Momentum',
                    'deskripsi' => 'Calculation of auspicious days for the next 3 months based on your Weton Neptu and project conditions.',
                    'pertanyaan' => [
                        'Are you planning to execute a project or open a business within the next three months?',
                        'Will this business involve collaboration with a partner?',
                        'Do you believe choosing the wrong day can affect the smoothness of a venture?',
                        'Is the main capital for this venture purely from your own personal funds?',
                        'Are you currently bound by an unfinished active contract with another party?',
                        'Have you set a concrete target date for launching your project or business?',
                        'Are any close family members involved in the venture you\'re about to run?',
                        'Have you repeatedly postponed a major decision because you felt the timing wasn\'t right?',
                        'Has your business plan already been discussed and fully supported by those closest to you?',
                        'Are you planning to perform a special ritual or prayer before starting this new venture?',
                    ],
                ],
                4 => [
                    'nama'      => 'Business Location & Storefront Direction',
                    'deskripsi' => 'Modern feng shui analysis — desk, cashier, and door positioning, plus layout recommendations based on the owner\'s birth element.',
                    'pertanyaan' => [
                        'Is your current business location or storefront positioned at a T-junction or road bend?',
                        'Do you often feel the atmosphere inside your workplace or shop is too hot or uncomfortable?',
                        'Does your cashier or work desk directly face the main entrance?',
                        'Is your storefront or business location currently rented or leased (not owned)?',
                        'Do you want to change your shop\'s interior layout soon to attract more customers?',
                        'Is there a large mirror directly facing the main entrance of your shop or business?',
                        'Is the storage or warehouse area at your business currently messy or hard to access?',
                        'Have you ever sensed an uncomfortable or "heavy" aura when customers first walk in?',
                        'Does the dominant interior color of your shop or workspace feel too dark or gloomy?',
                        'Are you planning to add a water element (aquarium or fountain) to your business area?',
                    ],
                ],
                5 => [
                    'nama'      => 'Deep Love Compatibility Calculator',
                    'deskripsi' => 'Elemental compatibility with your partner and relationship psychology analysis. Produces an empathetic narrative about your relationship dynamics plus a communication guide.',
                    'pertanyaan' => [
                        'Has your relationship with your partner recently been marked by frequent arguments over trivial matters?',
                        'Do you feel that family members, like parents or siblings, are less than fully supportive of this relationship?',
                        'Do you and your partner have very contrasting personalities?',
                        'Are you planning to take this relationship to marriage soon?',
                        'Do you often feel anxious or insecure about your partner\'s faithfulness?',
                        'Do you and your partner have significant differences in cultural or religious background?',
                        'Has communication between you and your partner felt increasingly reduced or flat lately?',
                        'Is there a past wound or betrayal that still affects your relationship dynamics today?',
                        'Have you and your partner already agreed on a shared, openly discussed financial plan?',
                        'Do you feel happier and more like yourself when you\'re with your partner?',
                    ],
                ],
                6 => [
                    'nama'      => 'Workplace Toxicity & Office Politics Radar',
                    'deskripsi' => 'Your career profile versus your current work environment. Produces strategies for dealing with toxic colleagues and the right timing for major career decisions.',
                    'pertanyaan' => [
                        'Do you feel your contributions at work are often unappreciated by your superiors?',
                        'Does your current work environment feel unhealthily competitive (toxic)?',
                        'Do you often feel anxious or stressed every time Monday arrives?',
                        'Are you planning to resign or switch divisions soon?',
                        'Do you currently have a close colleague at work you genuinely trust?',
                        'Do you have a mentor or senior actively guiding your career development?',
                        'Have you ever felt your work was claimed or credited to someone else without due recognition?',
                        'Do you feel your current salary or compensation doesn\'t match the contribution you provide?',
                        'Is there a major project or promotion opportunity you\'re actively pursuing right now?',
                        'Do you tend to avoid direct conflict and prefer resolving issues indirectly?',
                    ],
                ],
                7 => [
                    'nama'      => 'Child\'s Character & Parenting Style',
                    'deskripsi' => 'Analysis of your child\'s birth element versus your parenting style. Personalized communication recommendations based on the child\'s energy profile.',
                    'pertanyaan' => [
                        'Has your child recently been showing stubborn or hard-to-manage behavior?',
                        'Do you find it difficult to guess your child\'s main talent or interest right now?',
                        'Does your child tend to be shyer and more withdrawn in new environments?',
                        'Do you often run out of patience while helping your child study at home?',
                        'Do you want to adjust your communication style so your child listens to advice more?',
                        'Does your child spend more time on gadgets than physical activity outdoors?',
                        'Do you feel your child has a unique talent or intelligence that hasn\'t been fully developed?',
                        'Do you and your partner have different parenting styles that sometimes confuse your child?',
                        'Has your child ever shown signs of excessive anxiety at school or in new social settings?',
                        'Do you regularly set aside quality time alone with your child, free from gadgets or work?',
                    ],
                ],
                8 => [
                    'nama'      => 'Name Audit & Analysis',
                    'deskripsi' => 'Numerological analysis of your name and birth element. Suggestions for balancing initials to harmonize your energy.',
                    'pertanyaan' => [
                        'Do you feel your life is always heavy or unlucky despite working hard?',
                        'Is the name you use professionally different from the name on your birth certificate?',
                        'Do you have plans to create a new business name, pen name, or stage name?',
                        'Do you often feel your name carries a meaning misaligned with your parents\' hopes for you?',
                        'Do you want to know which additional initial letters could balance your name\'s energy?',
                        'Do other people often misspell or mispronounce your name in everyday interactions?',
                        'Does your name have an odd number of letters (e.g., 5, 7, or 9)?',
                        'Do you believe a certain vibration or energy is stored within the arrangement of letters in a name?',
                        'Are you developing a product, business, or brand that needs a new, energetically strong name?',
                        'Does the name you use daily already reflect the personality and aspirations you want to achieve?',
                    ],
                ],
                9 => [
                    'nama'      => 'Health & Body Energy Vitality',
                    'deskripsi' => 'Mapping of weak body zones based on your birth element, plus lifestyle guidance aligned with your personal energy rhythm.',
                    'pertanyaan' => [
                        'Do you often experience sleep disturbances (insomnia) or frequently wake up at night?',
                        'Do your shoulders, neck, or head often feel tense and sore without a clear medical reason?',
                        'Do you feel your energy and stamina run out quickly even when your activities aren\'t that demanding?',
                        'Are you currently on a diet or health recovery program?',
                        'Does your mood often change drastically and suddenly within a day?',
                        'Do you often consume high-sugar food or drinks as a stress outlet?',
                        'Have you ever been diagnosed with, or suspected of having, a hormonal or metabolic disorder?',
                        'Do you exercise regularly at least 3 times a week?',
                        'Do you often experience digestive issues (stomach or intestines) with no clear medical cause?',
                        'Do you notice a significant difference in your energy levels during rainy season versus dry season?',
                    ],
                ],
                10 => [
                    'nama'      => 'Cleansing Ritual & Bad Luck Mitigation Guide',
                    'deskripsi' => 'Detection of your current-year Ciong (clashing) position and a prayer/ritual guide for warding off misfortune.',
                    'pertanyaan' => [
                        'Did you know your Shio or Zodiac is in a Ciong (unlucky clash) position this year?',
                        'In the past three months, have you experienced a string of real misfortune?',
                        'Do you feel a negative energy or unseen burden weighing on your mind?',
                        'Are you willing to spend ten to fifteen minutes a day doing self-guided meditation?',
                        'Would you like to know your unlucky days this month so you can delay major decisions?',
                        'In the past year, have you experienced job loss, a breakup, or the loss of a loved one?',
                        'Is there a particular relationship in your life that consistently feels like it brings negative energy?',
                        'Have you previously undergone a cleansing ritual whose results didn\'t feel optimal?',
                        'Do you experience recurring nightmares or strange phenomena that disturb your peace of mind?',
                        'Are you willing to change certain habits or routines to neutralize negative energy in your life?',
                    ],
                ],
                11 => [
                    'nama'      => 'Daily Lucky Numbers & Colors',
                    'deskripsi' => 'A daily output of 3 lucky numbers + 2 outfit colors + a daily mantra. Exclusive to active subscribers.',
                    'pertanyaan' => [
                        'Do you often feel confused about what color to wear to an important event?',
                        'Do you believe certain numbers carry a lucky frequency for your life?',
                        'Do you often use number combinations for important purposes like PINs or license plates?',
                        'Would you like to receive these daily lucky numbers and colors directly via WhatsApp?',
                        'Do you feel the color you wear today affects your level of confidence?',
                    ],
                ],
                12 => [
                    'nama'      => 'Dream Interpretation',
                    'deskripsi' => 'A basic interpretation and lucky number are given for free. A dream with a bad meaning unlocks the Self-Protection Package paywall.',
                    'pertanyaan' => [],
                    'pertanyaan_terbuka' => 'What specific object or event do you remember most from last night\'s dream? (Example: bitten by a snake, a tooth falling out, a house on fire)',
                ],
            ],
        ];
    }

    public static function semua(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $meta   = self::meta();
        $text   = self::text()[$locale] ?? self::text()['id'];

        $hasil = [];
        foreach ($meta as $id => $m) {
            $hasil[$id] = array_merge($m, $text[$id] ?? []);
        }

        return $hasil;
    }

    public static function cari(int $id, ?string $locale = null): ?array
    {
        return self::semua($locale)[$id] ?? null;
    }

    public static function harga(int $id): int
    {
        return self::cari($id)['harga'] ?? 29000;
    }

    public static function pertanyaan(int $id, ?string $locale = null): array
    {
        return self::cari($id, $locale)['pertanyaan'] ?? [];
    }
}
