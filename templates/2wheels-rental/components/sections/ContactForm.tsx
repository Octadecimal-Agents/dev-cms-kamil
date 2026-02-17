'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useState, useEffect } from 'react';
import { useSearchParams } from 'next/navigation';
import { submitReservation } from '@/lib/api';
import type { ContactData, Motorcycle } from '@/lib/api';

interface ContactFormProps {
  contact: ContactData;
  bikes: Motorcycle[];
}

const formSchema = z.object({
  name: z.string().min(2, 'Imię i nazwisko jest wymagane'),
  email: z.string().email('Nieprawidłowy adres email'),
  phone: z.string().min(9, 'Numer telefonu musi mieć co najmniej 9 cyfr'),
  subject: z.string().min(1, 'Temat jest wymagany').optional(),
  bikeId: z.string().optional(),
  pickupDate: z.string().min(1, 'Data odbioru jest wymagana'),
  pickupTime: z.string().min(1, 'Godzina odbioru jest wymagana'),
  returnDate: z.string().min(1, 'Data zwrotu jest wymagana'),
  returnTime: z.string().min(1, 'Godzina zwrotu jest wymagana'),
  message: z.string().max(1000),
  consent: z.boolean().refine(val => val === true, 'Musisz zaakceptować regulamin i politykę prywatności'),
});

type FormData = z.infer<typeof formSchema>;

export default function ContactForm({ contact, bikes }: ContactFormProps) {
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const searchParams = useSearchParams();
  const bikeIdFromUrl = searchParams.get('bike');
  
  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      bikeId: bikeIdFromUrl || undefined,
    },
  });

  const selectedBikeId = watch('bikeId');
  const selectedBike = bikes.find(b => b.id === selectedBikeId);

  useEffect(() => {
    // Funkcja do przewijania do formularza
    const scrollToForm = () => {
      setTimeout(() => {
        const element = document.getElementById('rezerwacja');
        if (element) {
          element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 100);
    };

    // Obsługa parametru URL
    if (bikeIdFromUrl) {
      setValue('bikeId', bikeIdFromUrl);
      scrollToForm();
    }

    // Obsługa hash w URL (#rezerwacja?bike=id)
    if (typeof window !== 'undefined') {
      const hash = window.location.hash;
      if (hash.includes('rezerwacja')) {
        const urlParams = new URLSearchParams(hash.split('?')[1] || '');
        const bikeIdFromHash = urlParams.get('bike');
        if (bikeIdFromHash) {
          setValue('bikeId', bikeIdFromHash);
          scrollToForm();
        } else if (hash === '#rezerwacja') {
          scrollToForm();
        }
      }
    }

    // Obsługa localStorage (z BikeCard)
    if (typeof window !== 'undefined') {
      const savedBike = localStorage.getItem('selectedBike');
      if (savedBike) {
        try {
          const { id } = JSON.parse(savedBike);
          setValue('bikeId', id);
          localStorage.removeItem('selectedBike');
          scrollToForm();
        } catch (e) {
          // Ignoruj błędy parsowania
        }
      }
    }

    // Nasłuchuj na custom event z BikeCard
    function handleBikeSelected(event: CustomEvent<{ id: string; name: string }>) {
      const { id } = event.detail;
      setValue('bikeId', id);
      scrollToForm();
    }

    window.addEventListener('bikeSelected', handleBikeSelected as EventListener);
    return () => {
      window.removeEventListener('bikeSelected', handleBikeSelected as EventListener);
    };
  }, [bikeIdFromUrl, setValue]);

  const onSubmit = async (data: FormData) => {
    setIsSubmitting(true);
    setSubmitError(null);
    try {
      await submitReservation({
        motorcycle_id: data.bikeId || undefined,
        customer_name: data.name,
        customer_email: data.email,
        customer_phone: data.phone,
        pickup_date: data.pickupDate,
        return_date: data.returnDate,
        notes: data.message,
        rodo_consent: data.consent,
      });
      setIsSubmitted(true);
      setTimeout(() => setIsSubmitted(false), 10000);
    } catch (e) {
      setSubmitError(e instanceof Error ? e.message : 'Wystąpił błąd podczas wysyłania rezerwacji.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <section id="rezerwacja" className="py-20 bg-gray-light">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="font-heading text-4xl md:text-5xl font-bold mb-4">
            {contact.title}
          </h2>
          <p className="text-lg text-gray-medium max-w-2xl mx-auto">
            {contact.subtitle}
          </p>
        </div>

        <div className="max-w-2xl mx-auto">
          {isSubmitted ? (
            <div className="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg">
              <p className="font-semibold">Dziękujemy!</p>
              <p>Rezerwacja została przyjęta. Skontaktujemy się z Tobą wkrótce.</p>
            </div>
          ) : (
            <form onSubmit={handleSubmit(onSubmit)} className="bg-white p-8 rounded-xl shadow-md space-y-6">
              {/* Imię i nazwisko */}
              <div>
                <label className="block text-sm font-semibold mb-2">
                  {contact.form.namePlaceholder} *
                </label>
                <input
                  type="text"
                  {...register('name')}
                  placeholder={contact.form.namePlaceholder}
                  className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                />
                {errors.name && (
                  <p className="text-red-600 text-sm mt-1">{errors.name.message}</p>
                )}
              </div>

              {/* Email */}
              <div>
                <label className="block text-sm font-semibold mb-2">
                  {contact.form.emailPlaceholder} *
                </label>
                <input
                  type="email"
                  {...register('email')}
                  placeholder={contact.form.emailPlaceholder}
                  className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                />
                {errors.email && (
                  <p className="text-red-600 text-sm mt-1">{errors.email.message}</p>
                )}
              </div>

              {/* Telefon */}
              <div>
                <label className="block text-sm font-semibold mb-2">
                  Numer telefonu *
                </label>
                <input
                  type="tel"
                  {...register('phone')}
                  placeholder="np. 500 123 456"
                  className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                />
                {errors.phone && (
                  <p className="text-red-600 text-sm mt-1">{errors.phone.message}</p>
                )}
              </div>

              {/* Wybór motocykla */}
              <div>
                <label className="block text-sm font-semibold mb-2">
                  Wybierz motocykl
                </label>
                <select
                  {...register('bikeId')}
                  className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                >
                  <option value="">-- Wybierz motocykl --</option>
                  {bikes.map((bike) => (
                    <option key={bike.id} value={bike.id}>
                      {bike.name} ({bike.brand.name}) - {bike.price_per_day} zł/dzień
                    </option>
                  ))}
                </select>
                {selectedBike && (
                  <p className="text-sm text-gray-medium mt-2">
                    Wybrany: <span className="font-semibold">{selectedBike.name}</span> - {selectedBike.price_per_day} zł/dzień
                  </p>
                )}
              </div>

              {/* Temat (jeśli dostępny) */}
              {contact.form.subjectPlaceholder && (
                <div>
                  <label className="block text-sm font-semibold mb-2">
                    {contact.form.subjectPlaceholder}
                  </label>
                  <input
                    type="text"
                    {...register('subject')}
                    placeholder={contact.form.subjectPlaceholder}
                    className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                  />
                  {errors.subject && (
                    <p className="text-red-600 text-sm mt-1">{errors.subject.message}</p>
                  )}
                </div>
              )}

              {/* Data i godzina odbioru */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-semibold mb-2">
                    Data odbioru *
                  </label>
                  <input
                    type="date"
                    {...register('pickupDate')}
                    min={new Date().toISOString().split('T')[0]}
                    className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                  />
                  {errors.pickupDate && (
                    <p className="text-red-600 text-sm mt-1">{errors.pickupDate.message}</p>
                  )}
                </div>
                <div>
                  <label className="block text-sm font-semibold mb-2">
                    Godzina odbioru *
                  </label>
                  <input
                    type="time"
                    {...register('pickupTime')}
                    className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                  />
                  {errors.pickupTime && (
                    <p className="text-red-600 text-sm mt-1">{errors.pickupTime.message}</p>
                  )}
                </div>
              </div>

              {/* Data i godzina zwrotu */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-semibold mb-2">
                    Data zwrotu *
                  </label>
                  <input
                    type="date"
                    {...register('returnDate')}
                    min={new Date().toISOString().split('T')[0]}
                    className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                  />
                  {errors.returnDate && (
                    <p className="text-red-600 text-sm mt-1">{errors.returnDate.message}</p>
                  )}
                </div>
                <div>
                  <label className="block text-sm font-semibold mb-2">
                    Godzina zwrotu *
                  </label>
                  <input
                    type="time"
                    {...register('returnTime')}
                    className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                  />
                  {errors.returnTime && (
                    <p className="text-red-600 text-sm mt-1">{errors.returnTime.message}</p>
                  )}
                </div>
              </div>

              {/* Wiadomość */}
              <div>
                <label className="block text-sm font-semibold mb-2">
                  {contact.form.messagePlaceholder} (opcjonalnie)
                </label>
                <textarea
                  {...register('message')}
                  placeholder={contact.form.messagePlaceholder}
                  rows={4}
                  className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent-red"
                />
                {errors.message && (
                  <p className="text-red-600 text-sm mt-1">{errors.message.message}</p>
                )}
              </div>

              {/* Consent */}
              <div>
                <label className="flex items-start gap-2">
                  <input
                    type="checkbox"
                    {...register('consent')}
                    className="mt-1"
                  />
                  <span className="text-sm text-gray-medium">
                    {contact.form.consentText || 'Akceptuję regulamin i politykę prywatności.'} *
                  </span>
                </label>
                {errors.consent && (
                  <p className="text-red-600 text-sm mt-1">{errors.consent.message}</p>
                )}
              </div>

              {submitError && (
                <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                  {submitError}
                </div>
              )}

              {/* Submit */}
              <button
                type="submit"
                disabled={isSubmitting}
                className="w-full bg-accent-red text-white px-6 py-4 rounded-lg font-semibold text-lg hover:bg-red-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {isSubmitting ? 'Wysyłanie...' : contact.form.submitButton}
              </button>
            </form>
          )}
        </div>
      </div>
    </section>
  );
}
