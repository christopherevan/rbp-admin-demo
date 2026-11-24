import type { SVGAttributes } from 'react';

/** A conveyor belt over two pulleys: the fictional Kinetra mark. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 32 32"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect
                x="4"
                y="11"
                width="24"
                height="10"
                rx="5"
                stroke="currentColor"
                strokeWidth="2.5"
            />
            <circle cx="9" cy="16" r="2" fill="currentColor" />
            <circle cx="23" cy="16" r="2" fill="currentColor" />
            <rect
                x="12"
                y="5"
                width="8"
                height="5"
                rx="1"
                fill="currentColor"
            />
        </svg>
    );
}
