import type { Metadata } from 'next';
import Link from 'next/link';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { getAllContent } from '@/lib/api';

export const dynamic = 'force-dynamic';
export const revalidate = 0;

export const metadata: Metadata = {
  title: 'Regulamin | 2Wheels – Wypożyczalnia Motocykli',
  description: 'Regulamin wypożyczalni motocykli 2Wheels. Zasady wynajmu, rezerwacji, płatności i ubezpieczenia.',
};

const DEFAULT_REGULAMIN = `
<h2>1. Postanowienia ogólne</h2>
<p>Regulamin określa zasady wypożyczania motocykli, prawa i obowiązki wypożyczającego oraz najemcy.</p>
<h2>2. Warunki wynajmu</h2>
<p>Wynajem możliwy jest dla osób pełnoletnich, posiadających prawo jazdy kat. A od minimum 2 lat. Wymagana jest kaucja zwrotna.</p>
<h2>3. Rezerwacja i płatność</h2>
<p>Rezerwacja jest potwierdzona po wpłacie zaliczki. Pozostała kwota płatna przy odbiorze pojazdu. Akceptujemy płatności kartą oraz przelewem.</p>
<h2>4. Ubezpieczenie</h2>
<p>Wszystkie motocykle objęte są ubezpieczeniem OC i AC. Dodatkowo oferujemy rozszerzenie o ubezpieczenie assistance.</p>
<h2>5. Kontakt</h2>
<p>W sprawach regulaminu prosimy o kontakt: e-mail lub telefon podany w sekcji kontaktowej.</p>
`.trim();

export default async function RegulaminPage() {
  const content = await getAllContent();
  const html = content.site.regulaminContent || DEFAULT_REGULAMIN;

  return (
    <main className="min-h-screen">
      <Header site={content.site} navigation={content.navigation} />
      <section className="py-20 bg-white">
        <div className="container mx-auto px-4">
          <div className="mb-8">
            <Link href="/" className="text-accent-red hover:underline text-sm font-medium">
              ← Powrót na stronę główną
            </Link>
          </div>
          <div className="text-center mb-12">
            <h1 className="font-heading text-4xl md:text-5xl font-bold mb-4">Regulamin</h1>
            <p className="text-lg text-gray-medium max-w-2xl mx-auto">
              Zasady wypożyczania i korzystania z usług
            </p>
          </div>
          <div className="max-w-4xl mx-auto bg-gray-50 p-8 md:p-12 rounded-2xl shadow-md">
            <div
              className="prose prose-lg max-w-none prose-headings:font-heading prose-headings:text-primary-black prose-p:text-gray-700 prose-strong:text-accent-red prose-ul:list-disc prose-ul:pl-6 prose-li:text-gray-700 prose-blockquote:border-l-accent-red prose-blockquote:bg-white prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:italic"
              dangerouslySetInnerHTML={{ __html: html }}
            />
          </div>
        </div>
      </section>
      <Footer site={content.site} footer={content.footer} contact={content.contact} />
    </main>
  );
}
