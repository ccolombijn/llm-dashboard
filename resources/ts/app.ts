import './bootstrap';
import Alpine from 'alpinejs';
import dashboard from './dashboard';

import { aiClickwords } from "./app/ai/clickwords";
import { aiSummarize } from "./app/ai/summarize";
import { form } from "./app/form";
import { navResponsive } from "./app/navResponsive";
import { aiChat } from "./app/ai/chat";

aiClickwords();
aiSummarize();
aiChat();
form();
navResponsive();
window.Alpine = Alpine;
Alpine.data('dashboard', dashboard);
Alpine.start();
