#pragma once

#ifndef __natureofcode_1_ex_1_7__testApp__
#define __natureofcode_1_ex_1_7__testApp__

#include "ofMain.h"
#include "mover.h"

class testApp : public ofBaseApp{

	public:
		void setup();
		void update();
		void draw();

		void keyPressed  (int key);
		void keyReleased(int key);
		void mouseMoved(int x, int y );
		void mouseDragged(int x, int y, int button);
		void mousePressed(int x, int y, int button);
		void mouseReleased(int x, int y, int button);
		void windowResized(int w, int h);
		void dragEvent(ofDragInfo dragInfo);
		void gotMessage(ofMessage msg);
		
    Mover* movers = new Mover[20];
};

#endif