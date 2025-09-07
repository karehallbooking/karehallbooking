import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { motion, useScroll, useTransform } from 'framer-motion';
import { 
  School, 
  Calendar, 
  Users, 
  Bell, 
  CheckCircle, 
  ArrowRight,
  Clock,
  Star,
  MapPin,
  Phone,
  Mail,
  Wifi,
  Car,
  Utensils,
  Mic,
  Shield,
  DollarSign,
  FileText,
  Headphones,
  Sparkles,
  TrendingUp,
  Award,
  Heart
} from 'lucide-react';
import { AppNavbar } from '../components/AppNavbar';

export function Home() {
  const [typingText, setTypingText] = useState('');
  const [currentTextIndex, setCurrentTextIndex] = useState(0);
  const { scrollYProgress } = useScroll();
  const y = useTransform(scrollYProgress, [0, 1], [0, -50]);

  const subtitleTexts = [
    "Simple • Fast • Transparent",
    "Book • Manage • Enjoy",
    "Easy • Reliable • Secure"
  ];

  // Typing effect for subtitle
  useEffect(() => {
    const currentText = subtitleTexts[currentTextIndex];
    let index = 0;
    
    const typingInterval = setInterval(() => {
      if (index < currentText.length) {
        setTypingText(currentText.substring(0, index + 1));
        index++;
      } else {
        setTimeout(() => {
          setCurrentTextIndex((prev) => (prev + 1) % subtitleTexts.length);
          setTypingText('');
        }, 2000);
      }
    }, 100);

    return () => clearInterval(typingInterval);
  }, [currentTextIndex]);

  // Sample halls data
  const halls = [
    {
      id: 1,
      name: "K. S. Krishnan Auditorium",
      capacity: 500,
      image: "https://kalasalingam.ac.in/wp-content/uploads/2021/05/IMG_1827-scaled.jpg",
      facilities: ["AC", "Stage", "Audio", "Projector", "Microphones"],
      popular: true
    },
    {
      id: 2,
      name: "Dr. S. Radha Krishnan Senate Hall",
      capacity: 200,
      image: "https://thenews21.com/wp-content/uploads/2023/02/WhatsApp-Image-2023-02-26-at-8.23.14-PM-1-1024x682.jpeg",
      facilities: ["AC", "Stage", "Audio", "Conference Table"],
      popular: false
    },
    {
      id: 3,
      name: "Dr. A. P. J. Abdul Kalam Block Seminar Hall",
      capacity: 150,
      image: "https://i.ytimg.com/vi/mp9FMt4WnqI/maxresdefault.jpg?sqp=-oaymwEmCIAKENAF8quKqQMa8AEB-AHUBoAC4AOKAgwIABABGFMgQyh_MA8=&rs=AOn4CLCTbpdeDXSQi7jV0KFO1U2MF2SNNw",
      facilities: ["AC", "Stage", "Audio", "Whiteboard"],
      popular: true
    },
    {
      id: 4,
      name: "Admin Block Seminar Hall",
      capacity: 80,
      image: "https://th-i.thgim.com/public/incoming/xgoon1/article68068023.ece/alternates/FREE_1200/16April_Campus_Kalasa.jpg",
      facilities: ["AC", "Projector", "Whiteboard"],
      popular: false
    },
    {
      id: 5,
      name: "Srinivasa Ramanujam Block Seminar Hall",
      capacity: 120,
      image: "https://app.afternoonnews.in/storage/images/5/1Ff6G694j4thp0GWuXwp6i58oob3fpPIdVlJd3OF.jpg",
      facilities: ["AC", "Audio", "Projector", "Whiteboard", "Microphones"],
      popular: true
    },
    {
      id: 6,
      name: "Dr. V. Vasudevan Seminar Hall",
      capacity: 200,
      image: "https://th-i.thgim.com/public/incoming/8jwn1a/article68094565.ece/alternates/FREE_1200/23April_Campus_Kalasa.jpg",
      facilities: ["AC", "Projector", "Whiteboard", "Audio"],
      popular: false
    }
  ];

  // Sample testimonials
  const testimonials = [
    {
      id: 1,
      name: "Dr. Sarah Johnson",
      role: "Professor, Computer Science",
      review: "The booking system is incredibly user-friendly. I've been using it for all our department events.",
      event: "Annual Conference 2024",
      rating: 5
    },
    {
      id: 2,
      name: "Michael Chen",
      role: "Student President",
      review: "Quick approval process and excellent facilities. Highly recommended for student events!",
      event: "Cultural Fest 2024",
      rating: 5
    },
    {
      id: 3,
      name: "Dr. Emily Rodriguez",
      role: "Head of Department",
      review: "Professional service with real-time updates. Makes event planning so much easier.",
      event: "Research Symposium",
      rating: 5
    }
  ];

  // Stats data
  const stats = [
    { label: "Halls Available Today", value: halls.length, icon: School },
    { label: "Bookings Approved This Month", value: 27, icon: CheckCircle },
    { label: "Students Served", value: 1200, icon: Users }
  ];

  // Features data
  const features = [
    { icon: Clock, title: "Real-time availability check", description: "Instant booking status updates" },
    { icon: CheckCircle, title: "Easy approval process", description: "Streamlined admin workflow" },
    { icon: DollarSign, title: "Affordable pricing", description: "Transparent and fair rates" },
    { icon: FileText, title: "Transparent policies", description: "Clear terms and conditions" },
    { icon: Headphones, title: "24/7 Support", description: "Round-the-clock assistance" }
  ];

  // Animation variants
  const fadeInUp = {
    initial: { opacity: 0, y: 60 },
    animate: { opacity: 1, y: 0 },
    transition: { duration: 0.6 }
  };

  const staggerContainer = {
    animate: {
      transition: {
        staggerChildren: 0.1
      }
    }
  };

  const scaleIn = {
    initial: { scale: 0.8, opacity: 0 },
    animate: { scale: 1, opacity: 1 },
    transition: { duration: 0.5 }
  };

  return (
    <div className="min-h-screen bg-white">
      <AppNavbar showLoginButton={true} />
      
      {/* Hero Section */}
      <section className="relative min-h-screen flex items-center justify-center overflow-hidden">
        {/* Background with gradient overlay */}
        <div className="absolute inset-0 bg-gradient-to-br from-blue-900 via-blue-800 to-purple-900">
          <div className="absolute inset-0 bg-black/30"></div>
          {/* Animated particles */}
          <div className="absolute inset-0">
            {[...Array(50)].map((_, i) => (
              <motion.div
                key={i}
                className="absolute w-2 h-2 bg-white/20 rounded-full"
                animate={{
                  x: [0, 100, 0],
                  y: [0, -100, 0],
                  opacity: [0, 1, 0]
                }}
                transition={{
                  duration: 3 + Math.random() * 2,
                  repeat: Infinity,
                  delay: Math.random() * 2
                }}
                style={{
                  left: `${Math.random() * 100}%`,
                  top: `${Math.random() * 100}%`
                }}
              />
            ))}
          </div>
        </div>

        <motion.div 
          className="relative z-10 text-center text-white px-4 max-w-6xl mx-auto"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ duration: 1 }}
        >
          <motion.div
            initial={{ scale: 0.5, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ duration: 0.8, delay: 0.2 }}
            className="mb-8"
          >
            <div className="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-6">
              <School className="h-12 w-12 text-white" />
            </div>
          </motion.div>

          <motion.h1 
            className="text-5xl md:text-7xl font-bold mb-6"
            initial={{ y: 50, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.8, delay: 0.4 }}
          >
            Welcome to KARE Hall Booking System
          </motion.h1>

          <motion.div 
            className="text-2xl md:text-3xl font-light mb-8 h-12 flex items-center justify-center"
            initial={{ y: 30, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.8, delay: 0.6 }}
          >
            <span className="typing-text">{typingText}</span>
            <motion.span
              animate={{ opacity: [0, 1, 0] }}
              transition={{ duration: 1, repeat: Infinity }}
              className="ml-1"
            >
              |
            </motion.span>
          </motion.div>

          <motion.div
            initial={{ y: 30, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.8, delay: 0.8 }}
          >
            <Link
              to="/login"
              className="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white text-xl font-semibold rounded-full hover:from-blue-600 hover:to-purple-700 transition-all duration-300 shadow-2xl hover:shadow-blue-500/25 hover:scale-105"
            >
              <Sparkles className="mr-2 h-6 w-6" />
              Book a Hall Now
              <ArrowRight className="ml-2 h-6 w-6" />
            </Link>
          </motion.div>
        </motion.div>
      </section>

      {/* Available Halls Showcase */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            viewport={{ once: true }}
            className="text-center mb-16"
          >
            <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
              Available Halls
            </h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Choose from our premium selection of auditoriums and seminar halls
            </p>
            <p className="text-sm text-gray-500 mt-2">
              Showing {halls.length} halls available
            </p>
          </motion.div>

          <motion.div
            variants={staggerContainer}
            initial="initial"
            animate="animate"
            className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
          >
            {halls.map((hall, index) => (
              <motion.div
                key={hall.id}
                variants={fadeInUp}
                className="group relative bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300"
              >
                {hall.popular && (
                  <motion.div
                    className="absolute top-4 right-4 z-10"
                    animate={{ scale: [1, 1.1, 1] }}
                    transition={{ duration: 2, repeat: Infinity }}
                  >
                    <span className="bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                      Popular
                    </span>
                  </motion.div>
                )}
                
                <div className="relative overflow-hidden">
                  <img
                    src={hall.image}
                    alt={hall.name}
                    className="w-full h-48 object-cover group-hover:scale-110 transition-transform duration-500"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                </div>

                <div className="p-6">
                  <h3 className="text-xl font-bold text-gray-900 mb-2">{hall.name}</h3>
                  <p className="text-gray-600 mb-4">{hall.capacity} seats</p>
                  
                  <div className="flex flex-wrap gap-2 mb-6">
                    {hall.facilities.map((facility, idx) => (
                      <span
                        key={idx}
                        className="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full"
                      >
                        {facility}
                      </span>
                    ))}
                  </div>

                  <Link
                    to="/login"
                    className="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-300 text-center block"
                  >
                    Check Availability
                  </Link>
                </div>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* How It Works Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            viewport={{ once: true }}
            className="text-center mb-16"
          >
            <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
              How It Works
            </h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Simple steps to book your perfect hall
            </p>
          </motion.div>

          <div className="relative">
            {/* Connecting line */}
            <div className="hidden lg:block absolute top-24 left-0 right-0 h-0.5 bg-gradient-to-r from-blue-500 to-purple-600"></div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
              {[
                { step: "1️⃣", title: "Choose Hall", desc: "Select from available halls", icon: School },
                { step: "2️⃣", title: "Select Date", desc: "Pick your preferred date & time", icon: Calendar },
                { step: "3️⃣", title: "Admin Approval", desc: "Wait for approval notification", icon: CheckCircle },
                { step: "4️⃣", title: "Confirm Booking", desc: "Get confirmation & enjoy", icon: Award }
              ].map((item, index) => (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, y: 50 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.6, delay: index * 0.2 }}
                  viewport={{ once: true }}
                  className="text-center relative"
                >
                  <div className="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <item.icon className="h-10 w-10 text-white" />
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 mb-2">{item.title}</h3>
                  <p className="text-gray-600">{item.desc}</p>
                </motion.div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Live Booking Status / Stats Section */}
      <section className="py-20 bg-gradient-to-r from-blue-600 to-purple-700">
        <div className="max-w-7xl mx-auto px-4">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            viewport={{ once: true }}
            className="text-center mb-16"
          >
            <h2 className="text-4xl md:text-5xl font-bold text-white mb-4">
              Live Booking Status
            </h2>
            <p className="text-xl text-blue-100 max-w-2xl mx-auto">
              Real-time statistics of our booking system
            </p>
          </motion.div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {stats.map((stat, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, scale: 0.8 }}
                whileInView={{ opacity: 1, scale: 1 }}
                transition={{ duration: 0.6, delay: index * 0.2 }}
                viewport={{ once: true }}
                className="text-center bg-white/10 backdrop-blur-sm rounded-2xl p-8 hover:bg-white/20 transition-all duration-300"
              >
                <motion.div
                  initial={{ scale: 0 }}
                  whileInView={{ scale: 1 }}
                  transition={{ duration: 0.5, delay: index * 0.2 + 0.3 }}
                  viewport={{ once: true }}
                  className="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4"
                >
                  <stat.icon className="h-8 w-8 text-white" />
                </motion.div>
                <motion.div
                  initial={{ opacity: 0 }}
                  whileInView={{ opacity: 1 }}
                  transition={{ duration: 1, delay: index * 0.2 + 0.5 }}
                  viewport={{ once: true }}
                  className="text-4xl font-bold text-white mb-2"
                >
                  {stat.value}+
                </motion.div>
                <p className="text-blue-100">{stat.label}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      

      {/* Call-to-Action Section */}
      <section className="py-20 bg-gradient-to-r from-blue-600 to-purple-700">
        <div className="max-w-4xl mx-auto text-center px-4">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            viewport={{ once: true }}
          >
            <h2 className="text-4xl md:text-5xl font-bold text-white mb-6">
              Ready to Book Your Hall?
            </h2>
            <p className="text-xl text-blue-100 mb-8">
              Reserve your hall in just a few clicks
            </p>
            <motion.div
              animate={{ scale: [1, 1.05, 1] }}
              transition={{ duration: 3, repeat: Infinity }}
            >
              <Link
                to="/login"
                className="inline-flex items-center px-12 py-6 bg-white text-blue-600 text-2xl font-bold rounded-full hover:bg-blue-50 transition-all duration-300 shadow-2xl hover:shadow-white/25"
              >
                <Sparkles className="mr-3 h-8 w-8" />
                Book a Hall Now
                <ArrowRight className="ml-3 h-8 w-8" />
              </Link>
            </motion.div>
          </motion.div>
        </div>
      </section>

      {/* Contact & Support Section */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            viewport={{ once: true }}
            className="text-center mb-16"
          >
            <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
              Contact & Support
            </h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Get in touch with us for any assistance
            </p>
          </motion.div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
            {/* Contact Information */}
            <motion.div
              initial={{ opacity: 0, x: -50 }}
              whileInView={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.6 }}
              viewport={{ once: true }}
              className="space-y-8"
            >
              <div className="bg-white rounded-2xl p-8 shadow-lg">
                <h3 className="text-2xl font-bold text-gray-900 mb-6">Get in Touch</h3>
                <div className="space-y-4">
                  <div className="flex items-center">
                    <MapPin className="h-6 w-6 text-blue-600 mr-4" />
                    <span className="text-gray-700">KALASALINGAM ACADEMY OF RESEARCH AND EDUCATION, Srivilliputtur, Tamil Nadu 626126, India</span>
                  </div>
                  <div className="flex items-center">
                    <Phone className="h-6 w-6 text-blue-600 mr-4" />
                    <span className="text-gray-700">+91 4563 234567 / +91 4563 234568</span>
                  </div>
                  <div className="flex items-center">
                    <Mail className="h-6 w-6 text-blue-600 mr-4" />
                    <span className="text-gray-700">karehallbooking@gmail.com</span>
                  </div>
                </div>
              </div>

              {/* Quick Contact Form */}
              <motion.div
                initial={{ opacity: 0, y: 50 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: 0.2 }}
                viewport={{ once: true }}
                className="bg-white rounded-2xl p-8 shadow-lg"
              >
                <h3 className="text-2xl font-bold text-gray-900 mb-6">Quick Message</h3>
                <form className="space-y-4">
                  <input
                    type="text"
                    placeholder="Your Name"
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <input
                    type="email"
                    placeholder="Your Email"
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <textarea
                    placeholder="Your Message"
                    rows={4}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  ></textarea>
                  <button
                    type="submit"
                    className="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-300"
                  >
                    Send Message
                  </button>
                </form>
              </motion.div>
            </motion.div>

            {/* Map */}
            <motion.div
              initial={{ opacity: 0, x: 50 }}
              whileInView={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.6 }}
              viewport={{ once: true }}
              className="bg-white rounded-2xl p-8 shadow-lg"
            >
              <h3 className="text-2xl font-bold text-gray-900 mb-6">Our Location</h3>
              <div className="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center relative overflow-hidden">
                <div className="text-center">
                  <MapPin className="h-16 w-16 text-blue-600 mx-auto mb-4" />
                  <p className="text-gray-600 mb-2">KALASALINGAM ACADEMY OF RESEARCH AND EDUCATION</p>
                  <p className="text-sm text-gray-500 mb-4">Srivilliputtur, Tamil Nadu 626126, India</p>
                  <a
                    href="https://www.bing.com/maps?pglt=299&q=kalasalingam+academy+location&cvid=3de03232dc30499180cabb38e9241863&gs_lcrp=EgRlZGdlKgYIABBFGDkyBggAEEUYOdIBCTEwMTc4ajBqMagCALACAA&FORM=ANNTA1&PC=ASTS"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                  >
                    <MapPin className="h-5 w-5 mr-2" />
                    View on Bing Maps
                  </a>
                </div>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="max-w-7xl mx-auto px-4 text-center">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            viewport={{ once: true }}
          >
            <div className="flex items-center justify-center mb-6">
              <School className="h-8 w-8 text-blue-400 mr-3" />
              <span className="text-2xl font-bold">KARE Hall Booking</span>
            </div>
            <p className="text-gray-400 mb-6">
              © 2024 KALASALINGAM ACADEMY OF RESEARCH AND EDUCATION. All rights reserved.
            </p>
            <div className="flex justify-center space-x-6">
              <Link to="/login" className="text-gray-400 hover:text-white transition-colors">
                Login
              </Link>
              <Link to="/register" className="text-gray-400 hover:text-white transition-colors">
                Register
              </Link>
            </div>
          </motion.div>
        </div>
      </footer>
    </div>
  );
}