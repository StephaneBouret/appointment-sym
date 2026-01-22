import "./stimulus_bootstrap.js";
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import "bootstrap/dist/css/bootstrap.min.css";
import "@fortawesome/fontawesome-free/css/all.css";
import "bootstrap-icons/font/bootstrap-icons.min.css";
import "./styles/app.css";
import "bootstrap";

// Close alert message after 5 secondes
const closeAlertMessage = () => {
    const alert = document.querySelector(".alert");
    if (alert) {
        setTimeout(() => {
            alert.classList.add("fade-out"); // Ajoutez une classe CSS pour transition
            setTimeout(() => alert.remove(), 1000); // Retire l'alerte après la transition
        }, 4000);
    }
};

// Easy selector helper function
const select = (el, all = false) => {
    el = el.trim();
    if (all) {
        return [...document.querySelectorAll(el)];
    } else {
        return document.querySelector(el);
    }
};

// Easy on scroll event listener
const onscroll = (el, listener) => {
    el.addEventListener("scroll", listener);
};

// Back to top button
const backToTop = () => {
    const backtotop = select(".back-to-top");
    if (backtotop) {
        const toggleBacktotop = () => {
            backtotop.classList.toggle("active", window.scrollY > 100);
        };
        window.addEventListener("load", toggleBacktotop);
        onscroll(document, toggleBacktotop);
    }
};

const initPage = () => {
    closeAlertMessage();
    backToTop();
}

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");
document.addEventListener('load', initPage);
document.addEventListener('turbo:load', initPage);
