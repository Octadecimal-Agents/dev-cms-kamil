'use client';

import { useEffect, useState } from 'react';
import Image from 'next/image';
import { getAssetPath } from '@/lib/paths';
import type { WhyUsData } from '@/lib/api';

interface WhyUsProps {
  whyUs: WhyUsData;
}

// Konfiguracja API dla client-side (NEXT_PUBLIC_* wstrzykiwane przy build)
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'https://dev.octadecimal.studio/api/2wheels';
const API_DOMAIN = process.env.NEXT_PUBLIC_API_DOMAIN || 'https://dev.octadecimal.studio';
const TENANT_ID = process.env.NEXT_PUBLIC_TENANT_ID || 'a0e1ef09-91b0-476a-aec1-45ae89c36bd4';

function buildWhyUsApiUrl(path: string): string {
  const url = new URL(`${API_BASE_URL}${path}`);
  url.searchParams.set('tenant_id', TENANT_ID);
  url.searchParams.set('_t', String(Date.now())); // cache-buster dla świeżej treści "O nas"
  return url.toString();
}

function getStorageUrl(path: string | null | undefined): string {
  if (!path) return '/img/placeholder.jpg';
  if (path.startsWith('http')) return path;
  if (path.startsWith('/storage/')) return `${API_DOMAIN}${path}`;
  return path;
}

export default function WhyUs({ whyUs: initialWhyUs }: WhyUsProps) {
  const [whyUs, setWhyUs] = useState<WhyUsData>(initialWhyUs);

  // Pobierz świeże dane z API przy każdym montowaniu (treść "O nas" z CMS)
  useEffect(() => {
    async function fetchFreshData() {
      try {
        const siteResponse = await fetch(buildWhyUsApiUrl('/site-setting'), {
          cache: 'no-store',
          headers: { Accept: 'application/json' },
        });
        
        if (siteResponse.ok) {
          const siteData = await siteResponse.json();
          const aboutUsContent = siteData.data?.about_us_content ?? null;
          
          const featuresUrl = new URL(`${API_BASE_URL}/features`);
          featuresUrl.searchParams.set('tenant_id', TENANT_ID);
          const featuresResponse = await fetch(featuresUrl.toString(), {
            cache: 'no-store',
            headers: { Accept: 'application/json' },
          });
          
          let features = initialWhyUs.features;
          if (featuresResponse.ok) {
            const featuresData = await featuresResponse.json();
            features = (featuresData.data || [])
              .sort((a: any, b: any) => (a.order || 0) - (b.order || 0))
              .map((f: any) => ({
                id: f.id,
                title: f.title,
                description: f.description,
                icon: getStorageUrl(f.icon?.url),
                order: f.order,
              }));
          }
          
          setWhyUs(prev => ({
            ...prev,
            aboutUsContent: aboutUsContent ?? prev.aboutUsContent,
            features: features.length > 0 ? features : prev.features,
          }));
        }
      } catch (error) {
        console.error('Error fetching fresh WhyUs data:', error);
        // Zachowaj początkowe dane w przypadku błędu
      }
    }
    
    fetchFreshData();
  }, [initialWhyUs.features]);

  return (
    <section id="o-nas" className="py-20 bg-gray-light">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="font-heading text-4xl md:text-5xl font-bold mb-4">
            {whyUs.title}
          </h2>
          <p className="text-lg text-gray-medium max-w-2xl mx-auto">
            {whyUs.subtitle}
          </p>
        </div>

        {/* Treść "O nas" z CMS */}
        {whyUs.aboutUsContent && (
          <div className="max-w-4xl mx-auto mb-16 bg-white p-8 md:p-12 rounded-2xl shadow-lg">
            <div 
              className="prose prose-lg max-w-none prose-headings:font-heading prose-headings:text-primary-black prose-p:text-gray-700 prose-strong:text-accent-red prose-ul:list-disc prose-ul:pl-6 prose-li:text-gray-700 prose-blockquote:border-l-accent-red prose-blockquote:bg-gray-50 prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:italic"
              dangerouslySetInnerHTML={{ __html: whyUs.aboutUsContent }}
            />
          </div>
        )}

        {/* Features grid */}
        {whyUs.features.length > 0 && (
          <div className={`grid grid-cols-1 ${whyUs.features.length === 3 ? 'md:grid-cols-3' : 'md:grid-cols-2 lg:grid-cols-3'} gap-8 max-w-5xl mx-auto`}>
            {whyUs.features.map((feature, index) => (
              <div
                key={index}
                className="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow text-center"
              >
                {feature.icon ? (
                  <div className="w-16 h-16 mb-4 flex items-center justify-center mx-auto">
                    <Image
                      src={feature.icon.startsWith('http') ? feature.icon : getAssetPath(feature.icon)}
                      alt={feature.title}
                      width={64}
                      height={64}
                      className="w-full h-full"
                      unoptimized={feature.icon.startsWith('http')}
                    />
                  </div>
                ) : (
                  <div className="w-16 h-16 bg-accent-red rounded-full flex items-center justify-center mx-auto mb-4">
                    <span className="text-white text-2xl">⚙</span>
                  </div>
                )}
                <h3 className="font-heading text-xl font-bold mb-2">{feature.title}</h3>
                <p className="text-gray-medium">{feature.description}</p>
              </div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
