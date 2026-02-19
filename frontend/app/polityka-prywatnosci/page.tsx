import type { Metadata } from 'next';
import Link from 'next/link';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { getAllContent } from '@/lib/api';

export const dynamic = 'force-dynamic';
export const revalidate = 0;

export const metadata: Metadata = {
  title: 'Polityka prywatności | 2Wheels – Wypożyczalnia Motocykli',
  description: 'Polityka prywatności 2Wheels. Informacje o przetwarzaniu danych osobowych, cookies i Twoich prawach.',
};

const DEFAULT_POLITYKA = `
<h2>1. Administrator danych</h2>
<p>Administratorem Twoich danych osobowych jest wypożyczalnia 2Wheels. Kontakt w sprawach ochrony danych: adres e-mail oraz telefon podane w sekcji kontaktowej.</p>
<h2>2. Cele i podstawy przetwarzania</h2>
<p>Przetwarzamy dane w celu realizacji rezerwacji, obsługi zapytań oraz wypełnienia obowiązków prawnych (np. rachunkowość). Podstawą jest zgoda lub wykonanie umowy.</p>
<h2>3. Odbiorcy danych</h2>
<p>Dane mogą być udostępniane podmiotom obsługującym płatności, ubezpieczenia oraz dostawcom usług IT, którzy pomagają w działaniu strony i systemu rezerwacji.</p>
<h2>4. Okres przechowywania</h2>
<p>Dane przechowujemy przez okres wymagany przepisami prawa oraz do czasu przedawnienia ewentualnych roszczeń.</p>
<h2>5. Twoje prawa</h2>
<p>Przysługuje Ci prawo dostępu, sprostowania, usunięcia danych, ograniczenia przetwarzania, przenoszenia danych oraz wniesienia skargi do Prezesa UODO.</p>
<h2>6. Pliki cookies</h2>
<p>Strona korzysta z plików cookies niezbędnych do działania (np. sesja). Możesz zmienić ustawienia przeglądarki w zakresie cookies.</p>
`.trim();

export default async function PolitykaPrywatnosciPage() {
  const content = await getAllContent();
  const html = content.site.politykaContent || DEFAULT_POLITYKA;

  return (
    <main className="min-h-screen">
      <Header site={content.site} navigation={content.navigation} />
      <section className="py-20 bg-gray-light">
        <div className="container mx-auto px-4">
          <div className="mb-8">
            <Link href="/" className="text-accent-red hover:underline text-sm font-medium">
              ← Powrót na stronę główną
            </Link>
          </div>
          <div className="text-center mb-12">
            <h1 className="font-heading text-4xl md:text-5xl font-bold mb-4">Polityka prywatności</h1>
            <p className="text-lg text-gray-medium max-w-2xl mx-auto">
              Informacje o przetwarzaniu danych osobowych
            </p>
          </div>
          <div className="max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-2xl shadow-md">
            <div
              className="prose prose-lg max-w-none prose-headings:font-heading prose-headings:text-primary-black prose-p:text-gray-700 prose-strong:text-accent-red prose-ul:list-disc prose-ul:pl-6 prose-li:text-gray-700 prose-blockquote:border-l-accent-red prose-blockquote:bg-gray-50 prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:italic"
              dangerouslySetInnerHTML={{ __html: html }}
            />
          </div>
        </div>
      </section>
      <Footer site={content.site} footer={content.footer} contact={content.contact} />
    </main>
  );
}
