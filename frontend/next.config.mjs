/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  output: 'standalone',
  images: {
    unoptimized: true,
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'tst.2wheels-rental.pl',
        pathname: '/storage/**',
      },
      {
        protocol: 'https',
        hostname: '2wheels-rental.pl',
        pathname: '/storage/**',
      },
      {
        protocol: 'https',
        hostname: 'dev.octadecimal.studio',
        pathname: '/storage/**',
      },
      {
        protocol: 'http',
        hostname: 'localhost',
        pathname: '/storage/**',
      },
    ],
  },
};

export default nextConfig;
