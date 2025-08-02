# WarNight Shell

## Açıklama

WarNight Shell, web sunucularının kontrolünü ele geçirmek ve ele geçirilen web sitelerini yönetmek için tasarlanmış, kötü amaçlı bir web kabuğudur. Bu araç, sızma testleri veya yasa dışı faaliyetler sırasında bir sunucuya erişim sağlamak ve onu manipüle etmek için geniş bir dizi yetenek sunar.

## Tehlikeli Özellikler

WarNight Shell, bir web sunucusunu tam kontrol altına almak için tasarlanmış tehlikeli işlevler sunar:

* **Kimlik Doğrulama Atlatma**: Varsayılan parola (`warnight`) ile basit bir kimlik doğrulama sağlar, bu da yetkisiz erişimi kolaylaştırır.
* **Kontrol Paneli**: Sunucu yazılımı, IP adresi, PHP sürümü, işletim sistemi ve disk kullanımı gibi hassas sunucu bilgilerini ifşa eder.
* **Komut Çalıştırma**: Sunucuda rastgele sistem komutlarının yürütülmesine izin vererek, saldırganın işletim sistemi düzeyinde kontrol sağlamasına olanak tanır.
* **Dosya Yönetimi**:
    * Dosyaları ve dizinleri gezme, silme, yeniden adlandırma, oluşturma ve yükleme yetenekleri ile sunucu dosyaları üzerinde tam kontrol sağlar.
    * Dizinleri ZIP olarak indirme özelliği, hassas verilerin toplu olarak sızdırılmasına olanak tanır.
* **Metin Düzenleyici**: Sunucudaki herhangi bir dosyanın içeriğini değiştirmeye izin vererek, web sitesi içeriğinin tahrif edilmesine veya kötü amaçlı kod eklenmesine olanak tanır.
* **SQL İstemcisi**:
    * Veritabanı bağlantılarını kurma ve SQL sorgularını çalıştırma yeteneği, web sitesinin veritabanına doğrudan erişim ve manipülasyon sağlar. Bu, kullanıcı verilerinin çalınmasına veya veritabanının silinmesine yol açabilir.
    * Yaygın yapılandırma dosyalarından veritabanı kimlik bilgilerini otomatik olarak algılama özelliği, saldırganın işini kolaylaştırır.
* **Hash Üretici**: Çeşitli algoritmalarla hash üretme yeteneği, parmak izi oluşturma veya ele geçirilen sistemlerdeki şifreleri kırmaya yardımcı olabilir.
* **URL İndirici**: Harici bir URL'den dosyaları sunucuya indirme yeteneği, kötü amaçlı yazılımın veya diğer kötü amaçlı araçların sunucuya kolayca yerleştirilmesine izin verir.
* **PHP Değerlendirici**: Rastgele PHP kodunu doğrudan sunucuda çalıştırma yeteneği, gelişmiş saldırılar, arka kapılar oluşturma veya sistemde kalıcılık sağlama için kritik bir özelliktir.
* **E-posta Gönderici**: Sunucu üzerinden e-posta gönderme yeteneği, ele geçirilmiş sunucuyu kimlik avı saldırıları veya spam göndermek için kullanmaya izin verir.
* **Port Tarayıcı**: Hedef IP adreslerindeki açık portları tarama özelliği, iç ağ keşfi veya diğer hedeflere yönelik saldırılar için kullanılabilir.
* **Web Tarayıcısı (Proxy)**: Sunucuyu bir web proxy'si olarak kullanma yeteneği, saldırganın gerçek IP adresini gizlemesine ve dahili ağlara erişmesine yardımcı olabilir.
* **Alan Bilgisi**: Alan adı WHOIS ve DNS bilgileri alma, hedefler hakkında istihbarat toplamak için kullanılabilir.
* **Base64 Dönüştürücü**: Metinleri Base64 ile kodlama ve kod çözme, komutları veya veri yüklerini gizlemek için kullanılabilir.
* **Discord Webhook Spammer**: Belirli bir Discord webhook URL'sine otomatik olarak mesaj gönderme yeteneği, rahatsız edici veya taciz edici amaçlar için kullanılabilir.

## Kurulum ve Riskler

WarNight Shell'i kurmak için, `warnshell.php` dosyasını savunmasız bir web sunucusunun web erişilebilir bir dizinine yüklemeniz yeterlidir. Bu dosyanın bir sunucuda bulunması, ciddi bir güvenlik ihlali anlamına gelir.

## Kötüye Kullanım

Bu araç, web sunucularını ele geçirmek, web sitelerini tahrif etmek, veri çalmak, kötü amaçlı yazılım dağıtmak ve sunucudan diğer ağlara saldırmak gibi kötü niyetli faaliyetler için tasarlanmıştır.

**UYARI**: Bu belgede açıklanan bilgiler yalnızca eğitim amaçlıdır ve etik sızma testleri veya güvenlik araştırmaları için kullanılmalıdır. Yetkisiz erişim veya yasa dışı faaliyetler için bu aracı kullanmak, ilgili yasalara göre cezalandırılabilir.
