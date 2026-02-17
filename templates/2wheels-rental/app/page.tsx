import { Suspense } from 'react';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import Hero from '@/components/sections/Hero';
import WhyUs from '@/components/sections/WhyUs';
import Fleet from '@/components/sections/Fleet';
import HowItWorks from '@/components/sections/HowItWorks';
import Pricing from '@/components/sections/Pricing';
import Terms from '@/components/sections/Terms';
import Gallery from '@/components/sections/Gallery';
import Testimonials from '@/components/sections/Testimonials';
import Location from '@/components/sections/Location';
import ContactForm from '@/components/sections/ContactForm';
import { getAllContent, getMotorcycles } from '@/lib/api';

// Force dynamic rendering - fetch fresh data on every request
export const dynamic = 'force-dynamic';
export const revalidate = 0;

export default async function Home() {
  // Pobierz content i motorcycles równolegle (zgodnie z umową API)
  const [content, motorcycles] = await Promise.all([
    getAllContent(),
    getMotorcycles({ slug: '2wheels-rental.pl', per_page: 20 })
  ]);

  return (
    <main className="min-h-screen">
      <Header site={content.site} navigation={content.navigation} />
      <Hero hero={content.hero} />
      <HowItWorks howItWorks={content.howItWorks} />
      <Fleet fleet={content.fleet} initialBikes={motorcycles.data} totalBikes={motorcycles.meta.total} />
      <WhyUs whyUs={content.whyUs} />
      <Pricing pricing={content.pricing} bikes={motorcycles.data} />
      <Terms terms={content.terms} />
      <Gallery gallery={content.gallery} bikes={motorcycles.data} />
      <Testimonials testimonials={content.testimonials} />
      <Location location={content.location} contact={content.contact} />
      <Suspense fallback={<div className="py-20 bg-gray-light"><div className="container mx-auto px-4 text-center">Ładowanie formularza...</div></div>}>
        <ContactForm contact={content.contact} bikes={motorcycles.data} />
      </Suspense>
      <Footer site={content.site} footer={content.footer} contact={content.contact} />
    </main>
  );
}
