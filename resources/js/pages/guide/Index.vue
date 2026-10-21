<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlarmClock,
    Baby,
    Beef,
    Bell,
    BookOpen,
    Calculator,
    Camera,
    ChartColumnBig,
    CircleHelp,
    CloudOff,
    Eye,
    Fish,
    FolderTree,
    HandCoins,
    LifeBuoy,
    Lightbulb,
    Milk,
    PieChart,
    Receipt,
    Scale,
    Search,
    ShieldCheck,
    Smartphone,
    Sprout,
    Store,
    Syringe,
    TrendingDown,
    TrendingUp,
    TriangleAlert,
    Wallet,
    Wheat,
    X,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import PageTitle from '@/components/khamar/PageTitle.vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'সাহায্য ও গাইড', href: '/guide' }],
    },
});

type Tone = 'paddy' | 'turmeric' | 'clay' | 'pond' | 'soil';

interface Topic {
    title: string;
    icon: Component;
    body: string[];
    links: { label: string; href: string }[];
}

interface TopicGroup {
    title: string;
    tone: Tone;
    topics: Topic[];
}

const toneClass: Record<Tone, string> = {
    paddy: 'bg-paddy/12 text-paddy',
    turmeric: 'bg-turmeric/20 text-soil',
    clay: 'bg-clay/12 text-clay',
    pond: 'bg-pond/12 text-pond',
    soil: 'bg-soil/10 text-soil',
};

const firstDay = [
    {
        title: 'শুরুর টাকা লিখুন',
        body: 'সেটআপ → হিসাব খাত-এ দেখে নিন হাতে নগদ, ব্যাংক, বিকাশ ঠিক আছে কিনা। তারপর "টাকা কোথায় আছে" পাতায় প্রতিটি জায়গায় আজ কত টাকা আছে সেটা জায়গা যোগ করার সময় শুরুর টাকা দিন।',
        href: '/money',
        label: 'টাকা কোথায় আছে',
    },
    {
        title: 'গরুগুলো যোগ করুন',
        body: 'যে গরু আগে থেকেই আছে সেগুলোও "নতুন গরু" দিয়ে যোগ করুন — "আগে থেকেই ছিল" বেছে দাম দিন। একসাথে কেনা অনেক গরু হলে "কয়টি গরু" ঘরে সংখ্যা দিন, একবারেই সব কার্ড তৈরি হবে।',
        href: '/animals/create',
        label: 'নতুন গরু',
    },
    {
        title: 'ফোনে নোটিফিকেশন চালু করুন',
        body: '"মনে করিয়ে দিন" পাতায় নোটিফিকেশন চালু চাপুন এবং ফোন অনুমতি চাইলে "Allow" দিন। আইফোনে আগে অ্যাপটি হোম স্ক্রিনে রাখতে হবে।',
        href: '/reminders',
        label: 'মনে করিয়ে দিন',
    },
    {
        title: 'অফলাইন পাতা একবার খুলুন',
        body: 'নেট থাকা অবস্থায় "অফলাইন এন্ট্রি" পাতা একবার খুলে রাখুন। তাহলে পরে নেট না থাকলেও এই পাতায় দুধ ও খাবার লেখা যাবে।',
        href: '/offline',
        label: 'অফলাইন এন্ট্রি',
    },
    {
        title: 'ডায়েরিতে প্রথম কথা লিখুন',
        body: 'আজ খামারে কী হলো, কী শিখলেন, সামনে কী লক্ষ্য — ডায়েরিতে দুই লাইন লিখে শুরু করুন। পরে মাস শেষে মিলিয়ে দেখা সহজ হবে।',
        href: '/diary',
        label: 'ডায়েরি',
    },
];

const groups: TopicGroup[] = [
    {
        title: 'দৈনিক কাজ',
        tone: 'paddy',
        topics: [
            {
                title: 'দুধের হিসাব — চার বেলা',
                icon: Milk,
                body: [
                    'প্রতিদিন সকাল, দুপুর, বিকাল ও রাত — যে বেলা দুধ দোয়ানো হলো সেই বেলা বেছে প্রতিটি গাভীর লিটার লিখুন।',
                    'নিচের তালিকায় আজ কোন বেলা কত হলো আর এ মাসে মোট কত, তা দেখা যায়।',
                    'দুধ বিক্রি হলে টাকার হিসাব আলাদা: "নতুন এন্ট্রি → আয়" দিন।',
                ],
                links: [{ label: 'দুধের হিসাব', href: '/milk' }],
            },
            {
                title: 'গরুকে খাবার দেওয়া — কেজিতে, বেলা ধরে',
                icon: Wheat,
                body: [
                    'কোন খাবার (ভুসি, খড়, ঘাস, দানাদার…) কত কেজি দিলেন আর কোন বেলায় — সেটা লিখুন।',
                    'কোন ক্যাটাগরির গরু খেলো সেটা বাছুন; এক খাবার কয়েক ক্যাটাগরিতে ভাগ করে কেজি দেওয়া যায়।',
                    'যতটুকু খাওয়ানো হলো ঠিক ততটুকুর দাম সেই ক্যাটাগরির খরচে যায়, বাকিটা স্টকে থাকে।',
                ],
                links: [
                    {
                        label: 'খাবার দেওয়া',
                        href: '/stock/move?mode=issue',
                    },
                ],
            },
            {
                title: 'নেট না থাকলে — অফলাইন এন্ট্রি',
                icon: CloudOff,
                body: [
                    'মাঠে নেট না থাকলে "অফলাইন এন্ট্রি" পাতায় দুধ ও খাবার লিখুন — লেখা ফোনেই জমা থাকে।',
                    'মেনুতে সংখ্যা দেখায় কয়টি এন্ট্রি এখনো পাঠানো বাকি। নেট এলে নিজে থেকেই সার্ভারে চলে যায়।',
                    'অ্যাপ বন্ধ করবেন না বা ব্রাউজারের ডেটা মুছবেন না যতক্ষণ বাকি এন্ট্রি আছে।',
                ],
                links: [{ label: 'অফলাইন এন্ট্রি', href: '/offline' }],
            },
        ],
    },
    {
        title: 'টাকা',
        tone: 'turmeric',
        topics: [
            {
                title: 'নতুন এন্ট্রি — খরচ ও আয়',
                icon: HandCoins,
                body: [
                    'যেকোনো টাকা খরচ বা আয় হলে "নতুন এন্ট্রি" দিন: ধরন (খরচ/আয়/টাকা সরানো…), কোন ক্যাটাগরির, কত টাকা, কোথা থেকে দিলেন।',
                    'দুধ, গোবর, সবজি বিক্রি — আয় হিসেবে দিন। কিন্তু গরু বিক্রি গরুর কার্ড থেকে করুন।',
                    'সব এন্ট্রি "খাতা (সব এন্ট্রি)"-তে তারিখ ধরে থাকে।',
                ],
                links: [
                    { label: 'নতুন এন্ট্রি', href: '/entries/create' },
                    { label: 'খাতা', href: '/entries' },
                ],
            },
            {
                title: 'টাকা কোথায় আছে',
                icon: Wallet,
                body: [
                    'হাতে নগদ, ব্যাংক, বিকাশ, কারো কাছে রাখা — প্রতিটি জায়গার আলাদা ব্যালান্স এখানে।',
                    'এক জায়গা থেকে আরেক জায়গায় টাকা সরালে (যেমন ব্যাংক থেকে তোলা) "সরানো" দিন; মোট টাকা ঠিক থাকে।',
                    'হাতের টাকা গুনে না মিললে এখানেই প্রথম দেখুন।',
                ],
                links: [{ label: 'টাকা কোথায় আছে', href: '/money' }],
            },
            {
                title: 'রসিদ ও মেমো',
                icon: Receipt,
                body: [
                    'এন্ট্রির সাথে রসিদ/মেমো নম্বর আর রসিদের ছবি তুলে দিন — পরে খোঁজা ও মেলানো সহজ।',
                    'অফলাইনে তোলা রসিদের ছবি নেট এলে পাঠানো হয়।',
                ],
                links: [{ label: 'নতুন এন্ট্রি', href: '/entries/create' }],
            },
        ],
    },
    {
        title: 'গরু',
        tone: 'clay',
        topics: [
            {
                title: 'গরুর তালিকা ও কার্ড',
                icon: Beef,
                body: [
                    'প্রতিটি গরুর নিজের কার্ড: ট্যাগ, ক্যাটাগরি, কেনা দাম, ওজন, খরচ ও যত্নের ইতিহাস।',
                    'কার্ড থেকেই ওজন লেখা, ক্যাটাগরি বদল, বিক্রি বা মৃত্যু লেখা হয়।',
                ],
                links: [{ label: 'গরুর তালিকা', href: '/animals' }],
            },
            {
                title: 'একসাথে কেনা গরু',
                icon: Beef,
                body: [
                    '"নতুন গরু"-তে "কয়টি গরু" ঘরে সংখ্যা দিন (যেমন ৫)। মোট দাম সমান ভাগে প্রতিটি গরুর কার্ডে বসে যায়।',
                    'পরে চাইলে প্রতিটির ট্যাগ বা নাম আলাদা করে এডিট করুন।',
                ],
                links: [{ label: 'নতুন গরু', href: '/animals/create' }],
            },
            {
                title: 'বিক্রি, মৃত্যু, ক্যাটাগরি বদল',
                icon: TrendingUp,
                body: [
                    'গরু বিক্রি হলে কার্ডে "বিক্রি লিখুন" দিন — দাম আর টাকা কোথায় এলো। লাভ-ক্ষতি নিজে হিসাব হয়।',
                    'মারা গেলে "মৃত্যু" লিখুন; বাকি দাম ক্ষতি হিসেবে ধরা হয়।',
                    'বাছুর বড় হয়ে গাভী বা মোটাতাজায় গেলে "ক্যাটাগরি বদল" দিন — আগের খরচ আগের ক্যাটাগরিতেই থাকে।',
                ],
                links: [{ label: 'গরুর তালিকা', href: '/animals' }],
            },
            {
                title: 'ওজন মাপা',
                icon: Scale,
                body: [
                    'মাঝে মাঝে ওজন (কেজি) লিখুন। দুটি ওজনের মাঝে দিনে কত কেজি বাড়লো তা দেখায়।',
                    'মোটাতাজা ব্যাচে ওজন বৃদ্ধির লক্ষ্য দিলে লক্ষ্য থেকে কতটা দূরে তা বোঝা যায়।',
                ],
                links: [{ label: 'গরুর তালিকা', href: '/animals' }],
            },
            {
                title: 'গর্ভ ও বাছুর',
                icon: Baby,
                body: [
                    'গরম আসা, বীজ দেওয়া, গর্ভ পরীক্ষা ও বাচ্চা হওয়া — গাভীর কার্ড বা এই পাতা থেকে লিখুন।',
                    'কোন গাভী কতদিনের গর্ভবতী, কবে বাচ্চা হবে, এখন কী যত্ন দরকার — এক নজরে দেখা যায়।',
                    'গর্ভ পরীক্ষা ও বাচ্চা হওয়ার দিন এলে সকালের তালিকায় মনে করিয়ে দেয়।',
                ],
                links: [{ label: 'গর্ভ ও বাছুর', href: '/breeding' }],
            },
            {
                title: 'মোটাতাজা ব্যাচ',
                icon: TrendingUp,
                body: [
                    'প্রতিটি মোটাতাজা দফা আলাদা ব্যাচ নম্বরে: কবে শুরু, কয়টি গরু, কত দিন চলছে।',
                    'ব্যাচের মোট খরচ, ওজন কত বাড়লো, আর বিক্রিতে আসল লাভ কত — সব এক জায়গায়।',
                ],
                links: [{ label: 'মোটাতাজা ব্যাচ', href: '/batches' }],
            },
        ],
    },
    {
        title: 'খাবার, ঘাস ও সার',
        tone: 'paddy',
        topics: [
            {
                title: 'মাল কেনা, নিজের ঘাস, স্টক',
                icon: Sprout,
                body: [
                    'ভুসি, খড়, ঔষধ, সার কিনলে "মাল কেনা"-তে কেজি ও দাম দিন — মাল আগে স্টকে ওঠে।',
                    'নিজের জমির ঘাস কাটলে "নিজের উৎপাদন (ঘাস)"-এ কত কেজি, বাজারদর আর কত শতাংশ জমি থেকে তা লিখুন।',
                    '"স্টকে কী আছে"-তে দেখায় কোনটা কত আছে আর এভাবে চললে কত দিন চলবে। কম পড়লে সকালে জানায়।',
                ],
                links: [
                    { label: 'মাল কেনা', href: '/stock/move?mode=purchase' },
                    { label: 'নিজের ঘাস', href: '/stock/move?mode=produce' },
                    { label: 'স্টক', href: '/stock' },
                ],
            },
            {
                title: 'কোথায় কত কেজি গেল',
                icon: ChartColumnBig,
                body: [
                    'ঘাস কত শতাংশ জমি থেকে এলো, কোন খাবার কোন ক্যাটাগরির কতটি গরু খেলো, সার কোন জমিতে কত পড়লো — কেজিতে।',
                    'এই হিসাব টাকার খাতার সাথে মেলানো থাকে।',
                ],
                links: [{ label: 'কেজির রিপোর্ট', href: '/reports/usage' }],
            },
        ],
    },
    {
        title: 'মাস শেষে',
        tone: 'soil',
        topics: [
            {
                title: 'সাধারণ খরচ ভাগ',
                icon: PieChart,
                body: [
                    'বিদ্যুৎ, সবার জন্য রাখা শ্রমিক — যে খরচ কোনো এক ক্যাটাগরির নয়, মাস শেষে এখান থেকে ভাগ করুন।',
                    'ভাগ কোন অনুপাতে হয় তা "ভাগ কীভাবে হয় (উদাহরণ)" পাতায় সংখ্যা দিয়ে দেখানো আছে।',
                ],
                links: [
                    { label: 'খরচ ভাগ', href: '/allocation' },
                    { label: 'উদাহরণ', href: '/guide/split' },
                ],
            },
            {
                title: 'মাস লক',
                icon: ShieldCheck,
                body: [
                    'মাসের সব হিসাব মিলে গেলে মাসটি লক করুন। লক করা মাসে আর নতুন এন্ট্রি বা বদল করা যায় না।',
                    'লক করার আগে খরচ ভাগ শেষ করে নিন।',
                ],
                links: [{ label: 'মাস লক', href: '/setup/periods' }],
            },
        ],
    },
    {
        title: 'মনে রাখা ও লেখা',
        tone: 'pond',
        topics: [
            {
                title: 'রিমাইন্ডার ও নোটিফিকেশন',
                icon: AlarmClock,
                body: [
                    'মাঠে দাঁড়িয়ে কিছু মনে পড়লে লিখে সময় দিন — "১ ঘণ্টা পরে", "কাল সকাল" এমন দ্রুত বোতামও আছে। প্রতিদিন বা প্রতি সপ্তাহে ঘুরে আসতেও পারে।',
                    'সময় হলে ফোনে নোটিফিকেশন আসে, সেখানেই "হয়ে গেছে" বা "১ ঘণ্টা পরে" চাপা যায়।',
                    'সকাল ৭টায় দিনের কাজের তালিকা আসে (গর্ভ পরীক্ষা, বাচ্চা হওয়া, ফুরিয়ে আসা খাবার, আজকের রিমাইন্ডার)। সন্ধ্যা ৭টায় দুধ বা খাবার লেখা বাকি থাকলে মনে করিয়ে দেয়।',
                ],
                links: [{ label: 'মনে করিয়ে দিন', href: '/reminders' }],
            },
            {
                title: 'খামার ডায়েরি ও লক্ষ্য',
                icon: BookOpen,
                body: [
                    'প্রতিদিনের কথা, ভুল, শিক্ষা লিখে রাখুন — পরে কী কাজ করেছে আর কী করেনি বোঝা যায়।',
                    'লক্ষ্য দিন (যেমন দৈনিক দুধ বা মাসে লাভ) — কতটা পূরণ হলো তা দেখায়।',
                ],
                links: [{ label: 'ডায়েরি', href: '/diary' }],
            },
        ],
    },
    {
        title: 'রিপোর্ট',
        tone: 'turmeric',
        topics: [
            {
                title: 'লাভ-ক্ষতি, সম্পদ ও বাজেট',
                icon: ChartColumnBig,
                body: [
                    '"আজকের খামার"-এ এক নজরে আজকের দুধ, টাকা আর কাজ।',
                    '"ক্যাটাগরি ও প্রজেক্ট"-এ প্রতিটি ভাগের আয়-খরচ-লাভ; "লাভ-ক্ষতি ও সম্পদ"-এ পুরো খামারের হিসাব; মাসিক রিপোর্টে মাস ধরে তুলনা।',
                    '"উদ্যোগ বাজেট"-এ ঠিক করা বাজেটের তুলনায় কত খরচ হলো।',
                ],
                links: [
                    { label: 'ক্যাটাগরি', href: '/centers' },
                    { label: 'লাভ-ক্ষতি', href: '/reports' },
                    { label: 'মাসিক', href: '/reports/monthly' },
                    { label: 'বাজেট', href: '/reports/budget' },
                ],
            },
        ],
    },
    {
        title: 'সেটআপ ও নিরাপত্তা',
        tone: 'soil',
        topics: [
            {
                title: 'প্রজেক্ট সাজান',
                icon: FolderTree,
                body: [
                    'প্রজেক্ট ও ক্যাটাগরি এক জায়গায়: নাম বদল, ওপরে-নিচে সরানো, ভেতরে নতুন যোগ।',
                    'চালু/বন্ধ মানে দেখা/লুকানো — বন্ধ করলে হিসাব মোছে না, শুধু তালিকায় দেখায় না।',
                    'মোছা যায় শুধু যদি ভেতরে কোনো এন্ট্রি বা গরু না থাকে।',
                ],
                links: [{ label: 'প্রজেক্ট সাজান', href: '/setup/centers' }],
            },
            {
                title: 'নিরাপত্তা, রোল ও ব্যাকআপ',
                icon: ShieldCheck,
                body: [
                    'প্রতিটি মানুষের নিজের লগইন; "রোল ও অনুমতি" দিয়ে ঠিক হয় কে কোন পাতা দেখবে বা লিখবে।',
                    'সেটিংসে দুই ধাপের যাচাই (২FA) চালু রাখুন। অডিট লগে কে কখন কী বদলালো সব থাকে।',
                    'প্রতি রাতে ২টার পর হিসাবের ব্যাকআপ হয়; "অ্যাপ আপডেট" থেকে নতুন সংস্করণ বসানো হয়।',
                ],
                links: [
                    { label: 'ব্যবহারকারী', href: '/setup/users' },
                    { label: 'রোল', href: '/setup/roles' },
                    { label: 'অডিট লগ', href: '/setup/audit' },
                    { label: 'আপডেট', href: '/setup/update' },
                ],
            },
        ],
    },
];

const faqs = [
    {
        q: 'ভুল এন্ট্রি মুছবো কীভাবে?',
        a: 'খাতার এন্ট্রি মোছা যায় না, যাতে এক টাকাও হারিয়ে না যায়। এন্ট্রি খুলে "বাতিল করুন" চাপুন — একটি উল্টো এন্ট্রি বসে আগেরটা শূন্য করে দেয়, আর অডিট লগে থেকে যায়। তারপর সঠিক এন্ট্রি দিন।',
    },
    {
        q: 'নেট না থাকলে কী করবো?',
        a: '"অফলাইন এন্ট্রি" পাতায় দুধ ও খাবার লিখুন। লেখা ফোনে জমা থাকে, নেট এলে নিজে থেকেই চলে যায়। পাতাটি আগে একবার নেট থাকা অবস্থায় খুলে রাখতে হয়।',
    },
    {
        q: 'এক বস্তা ভুসি দুই ক্যাটাগরিতে ভাগ করবো কীভাবে?',
        a: '"গরুকে খাবার দেওয়া"-তে একটির নিচে আরেকটি ক্যাটাগরি যোগ করুন এবং প্রত্যেকটিতে কত কেজি গেল লিখুন। যে যতটুকু খেলো, তার দাম ততটুকুই।',
    },
    {
        q: 'নিজের ঘাসের দাম কেন আয় দেখায়?',
        a: 'নিজের জমির ঘাস গরুকে দিলে ঘাস চাষে সেটা আয় আর গরুতে খরচ — তাতে বোঝা যায় কোন ভাগ আসলে লাভ করছে। পুরো খামারের মোট হিসাবে এটা বাদ যায়, কারণ টাকা খামারের বাইরে যায়নি।',
    },
    {
        q: 'সাধারণ খরচ কী?',
        a: 'যে খরচ কোনো এক ক্যাটাগরির নয় — বিদ্যুৎ, সবার জন্য রাখা শ্রমিক, পাহারা। এগুলো আগে "সাধারণ খরচ"-এ পড়ে, মাস শেষে "খরচ ভাগ" দিয়ে ক্যাটাগরিগুলোতে ভাগ হয়।',
    },
    {
        q: 'নোটিফিকেশন আসছে না কেন?',
        a: 'ফোনে এই সাইটের নোটিফিকেশন অনুমতি "Allow" আছে কিনা দেখুন, আর "মনে করিয়ে দিন" পাতায় চালু আছে কিনা। আইফোনে Safari-র শেয়ার বোতাম থেকে অ্যাপটি হোম স্ক্রিনে রেখে সেখান থেকে খুলতে হয়। ব্যাটারি সেভার অনেক সময় নোটিফিকেশন আটকায়।',
    },
    {
        q: 'গরু বিক্রি হলে তালিকা থেকে যায় কোথায়?',
        a: 'বিক্রি বা মৃত্যু হলে গরুটি চলতি তালিকা থেকে সরে যায়, কিন্তু মোছে না। গরুর তালিকার ওপরে অবস্থা বোতাম (যেমন বিক্রি হয়েছে) চাপলে পুরনো কার্ড, খরচ আর লাভ দেখা যায়।',
    },
    {
        q: 'বন্ধ করা প্রজেক্ট আবার চালু করা যায়?',
        a: 'হ্যাঁ। "প্রজেক্ট সাজান" পাতায় বন্ধ প্রজেক্টটি খুঁজে আবার চালু করুন। বন্ধ থাকা অবস্থায়ও তার সব হিসাব ঠিক থাকে।',
    },
    {
        q: 'হিসাব মিলছে না, হাতের টাকা কম বেশি?',
        a: '"টাকা কোথায় আছে"-তে জায়গা ধরে ব্যালান্স দেখুন, তারপর খাতায় সেই জায়গার এন্ট্রি তারিখ ধরে মিলান। কোনো খরচ লেখা বাকি থাকলে নতুন এন্ট্রি দিন।',
    },
    {
        q: 'ভুল মাসে এন্ট্রি দিয়ে ফেলেছি, মাস লক হয়ে গেছে?',
        a: 'লক করা মাসে বদল করা যায় না। হিসাব অ্যাডমিনকে বলুন — তিনি মাস খুলে ঠিক করে আবার লক করতে পারেন।',
    },
];

type Status = 'চলছে' | 'পরিকল্পনায়';

const roadmap: {
    title: string;
    why: string;
    icon: Component;
    status: Status;
}[] = [
    {
        title: 'Android অ্যাপ',
        why: 'অ্যালার্মের মতো রিংটোন বাজবে, আর নেট ছাড়াও রিমাইন্ডার ঠিক সময়ে আসবে।',
        icon: Smartphone,
        status: 'চলছে',
    },
    {
        title: 'টিকা ও কৃমির তালিকা',
        why: 'কোন গরুর কবে টিকা বা কৃমিনাশক বাকি, তা আগেভাগে জানাবে।',
        icon: Syringe,
        status: 'পরিকল্পনায়',
    },
    {
        title: 'দুধ হঠাৎ কমলে সতর্কতা',
        why: 'কোনো গাভীর দুধ কয়েক বেলা কমে গেলে অসুখের আগাম ইঙ্গিত হিসেবে জানাবে।',
        icon: TrendingDown,
        status: 'পরিকল্পনায়',
    },
    {
        title: 'ডায়েরিতে ছবি ও ভয়েস নোট',
        why: 'লিখতে কষ্ট হলে বলে রাখা যাবে; অসুস্থ গরুর ছবি ডাক্তারকে দেখানো সহজ।',
        icon: Camera,
        status: 'পরিকল্পনায়',
    },
    {
        title: 'বিনিয়োগকারীর জন্য শুধু-দেখার অ্যাকাউন্ট',
        why: 'বিনিয়োগকারী নিজেই লাভ-ক্ষতি দেখতে পারবেন, কিছু বদলাতে পারবেন না।',
        icon: Eye,
        status: 'পরিকল্পনায়',
    },
    {
        title: 'হাটের দাম ও বিক্রির পরিকল্পনা',
        why: 'ঈদের আগে কোন গরু কবে, কত দামে বিক্রি করলে লাভ বেশি — হিসাব করে দেখাবে।',
        icon: Store,
        status: 'পরিকল্পনায়',
    },
    {
        title: 'এসএমএস জরুরি সতর্কতা',
        why: 'বাচ্চা হওয়ার দিন বা জরুরি কাজে নেট না থাকলেও মালিকের ফোনে এসএমএস যাবে।',
        icon: TriangleAlert,
        status: 'পরিকল্পনায়',
    },
    {
        title: 'খাবারের রেশন হিসাব',
        why: 'গরুর ওজন অনুযায়ী দিনে কত কেজি ঘাস, খড় ও দানাদার দিতে হবে বলে দেবে।',
        icon: Calculator,
        status: 'পরিকল্পনায়',
    },
    {
        title: 'পুকুর ও মাছ',
        why: 'মাছের পোনা, খাবার ও বিক্রি আলাদা প্রজেক্ট হিসেবে লেখা যাবে।',
        icon: Fish,
        status: 'পরিকল্পনায়',
    },
    {
        title: 'দোকান প্রজেক্ট চালু',
        why: 'খামারের দুধ, ঘি, সবজি বিক্রির দোকানের হিসাব একই খাতায়।',
        icon: Store,
        status: 'পরিকল্পনায়',
    },
];

const query = ref('');

const needle = computed(() => query.value.trim().toLowerCase());

function matches(...texts: string[]): boolean {
    if (!needle.value) {
        return true;
    }

    return texts.join(' ').toLowerCase().includes(needle.value);
}

const visibleSteps = computed(() =>
    firstDay
        .map((step, i) => ({ ...step, n: i + 1 }))
        .filter((s) => matches(s.title, s.body, s.label)),
);

const visibleGroups = computed(() =>
    groups
        .map((g) => ({
            ...g,
            topics: g.topics.filter((t) =>
                matches(
                    g.title,
                    t.title,
                    ...t.body,
                    ...t.links.map((l) => l.label),
                ),
            ),
        }))
        .filter((g) => g.topics.length > 0),
);

const visibleFaqs = computed(() => faqs.filter((f) => matches(f.q, f.a)));

const visibleRoadmap = computed(() =>
    roadmap.filter((r) => matches(r.title, r.why, r.status)),
);

const nothingFound = computed(
    () =>
        !!needle.value &&
        !visibleSteps.value.length &&
        !visibleGroups.value.length &&
        !visibleFaqs.value.length &&
        !visibleRoadmap.value.length,
);

const bn = (n: number) => n.toLocaleString('bn-BD');
</script>

<template>
    <div class="flex flex-col gap-8 p-4 md:p-6">
        <PageTitle
            title="সাহায্য ও গাইড"
            subtitle="কোন কাজ কোথায় করবেন — সহজ ভাষায়। নিচে খুঁজুন অথবা ঘুরে দেখুন।"
        >
            <label class="relative mt-4 block max-w-xl">
                <span class="sr-only">গাইডে খুঁজুন</span>
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="query"
                    type="search"
                    placeholder="যেমন: দুধ, ভুসি, বাতিল, নোটিফিকেশন…"
                    class="h-11 w-full rounded-xl border border-border bg-card pr-10 pl-9 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-paddy focus-visible:ring-2 focus-visible:ring-paddy/25"
                />
                <button
                    v-if="query"
                    type="button"
                    class="absolute top-1/2 right-2 grid size-7 -translate-y-1/2 place-items-center rounded-md text-muted-foreground hover:bg-muted"
                    aria-label="খোঁজা মুছুন"
                    @click="query = ''"
                >
                    <X class="size-4" />
                </button>
            </label>
        </PageTitle>

        <p
            v-if="nothingFound"
            class="rounded-xl border border-dashed border-border bg-card p-6 text-center text-sm text-muted-foreground"
        >
            "{{ query }}" দিয়ে কিছু পাওয়া যায়নি। অন্য শব্দে খুঁজুন, যেমন
            "খাবার" বা "টাকা"।
        </p>

        <!-- 1. First day -->
        <section v-if="visibleSteps.length" aria-labelledby="first-day">
            <h2
                id="first-day"
                class="mb-3 flex items-center gap-2 font-display text-xl"
            >
                <Lightbulb class="size-5 text-turmeric" />
                প্রথম দিনের কাজ
            </h2>
            <ol class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <li
                    v-for="step in visibleSteps"
                    :key="step.n"
                    class="flex gap-3 rounded-xl border border-border bg-card p-4"
                >
                    <span
                        class="num grid size-8 shrink-0 place-items-center rounded-full bg-paddy font-semibold text-white"
                    >
                        {{ bn(step.n) }}
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-medium">{{ step.title }}</h3>
                        <p
                            class="mt-1 text-sm leading-relaxed text-muted-foreground"
                        >
                            {{ step.body }}
                        </p>
                        <Link
                            :href="step.href"
                            class="mt-2 inline-block text-sm font-medium text-paddy hover:underline"
                        >
                            {{ step.label }} খুলুন →
                        </Link>
                    </div>
                </li>
            </ol>
        </section>

        <!-- 2. Where to do what -->
        <section v-if="visibleGroups.length" aria-labelledby="daily">
            <h2
                id="daily"
                class="mb-3 flex items-center gap-2 font-display text-xl"
            >
                <Wheat class="size-5 text-paddy" />
                প্রতিদিন কোথায় কী করবেন
            </h2>
            <div class="flex flex-col gap-6">
                <div v-for="g in visibleGroups" :key="g.title">
                    <h3
                        class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{ g.title }}
                    </h3>
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="t in g.topics"
                            :key="t.title"
                            class="flex flex-col rounded-xl border border-border bg-card p-4"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="grid size-9 shrink-0 place-items-center rounded-lg"
                                    :class="toneClass[g.tone]"
                                >
                                    <component :is="t.icon" class="size-5" />
                                </span>
                                <h4 class="font-display text-base leading-snug">
                                    {{ t.title }}
                                </h4>
                            </div>
                            <ul
                                class="mt-3 flex flex-1 flex-col gap-1.5 text-sm leading-relaxed text-muted-foreground"
                            >
                                <li
                                    v-for="(line, i) in t.body"
                                    :key="i"
                                    class="flex gap-2"
                                >
                                    <span
                                        class="mt-2 size-1.5 shrink-0 rounded-full bg-clay/60"
                                    />
                                    <span>{{ line }}</span>
                                </li>
                            </ul>
                            <div
                                class="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-border/70 pt-3"
                            >
                                <Link
                                    v-for="l in t.links"
                                    :key="l.href"
                                    :href="l.href"
                                    class="text-sm font-medium text-paddy hover:underline"
                                >
                                    {{
                                        t.links.length > 1
                                            ? `${l.label} →`
                                            : 'খুলুন →'
                                    }}
                                </Link>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. FAQ -->
        <section v-if="visibleFaqs.length" aria-labelledby="faq">
            <h2
                id="faq"
                class="mb-3 flex items-center gap-2 font-display text-xl"
            >
                <CircleHelp class="size-5 text-pond" />
                প্রশ্ন ও উত্তর
            </h2>
            <div
                class="divide-y divide-border overflow-hidden rounded-xl border border-border bg-card"
            >
                <details
                    v-for="f in visibleFaqs"
                    :key="f.q"
                    class="group"
                    :open="!!needle"
                >
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-3 p-4 font-medium select-none hover:bg-muted/50 [&::-webkit-details-marker]:hidden"
                    >
                        <span>{{ f.q }}</span>
                        <span
                            class="text-lg leading-none text-muted-foreground transition-transform group-open:rotate-45"
                            aria-hidden="true"
                            >+</span
                        >
                    </summary>
                    <p
                        class="px-4 pb-4 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ f.a }}
                    </p>
                </details>
            </div>
        </section>

        <!-- 4. Roadmap -->
        <section v-if="visibleRoadmap.length" aria-labelledby="roadmap">
            <h2
                id="roadmap"
                class="mb-1 flex items-center gap-2 font-display text-xl"
            >
                <Sprout class="size-5 text-paddy" />
                সামনে যা যোগ করা যায়
            </h2>
            <p class="mb-3 text-sm text-muted-foreground">
                আপনার কোনটা আগে দরকার, হিসাব অ্যাডমিনকে জানান।
            </p>
            <ul class="grid gap-3 md:grid-cols-2">
                <li
                    v-for="r in visibleRoadmap"
                    :key="r.title"
                    class="flex gap-3 rounded-xl border border-border bg-card p-4"
                >
                    <span
                        class="grid size-9 shrink-0 place-items-center rounded-lg bg-soil/10 text-soil"
                    >
                        <component :is="r.icon" class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-medium">{{ r.title }}</h3>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    r.status === 'চলছে'
                                        ? 'bg-paddy/15 text-paddy'
                                        : 'bg-turmeric/25 text-soil'
                                "
                            >
                                {{ r.status }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ r.why }}
                        </p>
                    </div>
                </li>
            </ul>
        </section>

        <!-- 5. Footer -->
        <footer
            class="flex flex-col gap-3 rounded-xl border border-clay/30 bg-clay/5 p-4 text-sm"
        >
            <p class="flex gap-2">
                <Syringe class="mt-0.5 size-4 shrink-0 text-clay" />
                <span
                    >ঔষধ, মাত্রা ও টিকার তারিখ সবসময় প্রাণিসম্পদ ডাক্তারের
                    পরামর্শে।</span
                >
            </p>
            <p class="flex gap-2 text-muted-foreground">
                <LifeBuoy class="mt-0.5 size-4 shrink-0 text-pond" />
                <span>সমস্যা হলে: হিসাব অ্যাডমিন (Klok Ads)</span>
            </p>
            <p class="flex gap-2 text-muted-foreground">
                <Bell class="mt-0.5 size-4 shrink-0 text-turmeric" />
                <span
                    >সকাল ৭টায় দিনের তালিকা, সন্ধ্যা ৭টায় বাকি কাজের কথা —
                    ফোনে নোটিফিকেশন চালু রাখুন।</span
                >
            </p>
        </footer>
    </div>
</template>
